<?php
// Simple maintenance flag helper using a small settings table.
require_once __DIR__ . '/Database.php';

function get_setting_value(string $name) {
    try {
        $db = (new Database())->getConnection();
        // prefer system_settings table if present
        $tables = $db->query("SHOW TABLES LIKE 'system_settings'")->fetchAll();
        if (!empty($tables)) {
            $stmt = $db->prepare('SELECT `value` FROM `system_settings` WHERE `name` = :name LIMIT 1');
            $stmt->execute([':name' => $name]);
            $r = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($r) return $r['value'];
        }

        // fallback to settings table (legacy)
        $stmt = $db->prepare('SELECT `value` FROM `settings` WHERE `name` = :name LIMIT 1');
        $stmt->execute([':name' => $name]);
        $r = $stmt->fetch(PDO::FETCH_ASSOC);
        return $r ? $r['value'] : null;
    } catch (Exception $e) {
        error_log('[maintenance_check] DB error: ' . $e->getMessage());
        return null;
    }
}

function set_setting_value(string $name, $value) {
    try {
        $db = (new Database())->getConnection();
        // prefer updating system_settings if present
        $tables = $db->query("SHOW TABLES LIKE 'system_settings'")->fetchAll();
        if (!empty($tables)) {
            // ensure row exists
            $stmt = $db->prepare('SELECT id FROM system_settings WHERE `name` = :name LIMIT 1');
            $stmt->execute([':name' => $name]);
            $r = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($r) {
                $ust = $db->prepare('UPDATE system_settings SET `value` = :value WHERE `name` = :name');
                $ust->execute([':value' => $value, ':name' => $name]);
            } else {
                // insert with a default description
                $ist = $db->prepare('INSERT INTO system_settings (`name`,`value`,`description`) VALUES (:name,:value,:desc)');
                $ist->execute([':name' => $name, ':value' => $value, ':desc' => NULL]);
            }
            return true;
        }

        // fallback to settings table (create if missing)
        $db->exec("CREATE TABLE IF NOT EXISTS `settings` (
            `name` VARCHAR(128) NOT NULL PRIMARY KEY,
            `value` VARCHAR(255) DEFAULT NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
        $stmt = $db->prepare('INSERT INTO `settings` (`name`,`value`) VALUES (:name,:value)
            ON DUPLICATE KEY UPDATE `value` = :value2');
        $stmt->execute([':name' => $name, ':value' => $value, ':value2' => $value]);
        return true;
    } catch (Exception $e) {
        error_log('[maintenance_check] DB error on set: ' . $e->getMessage());
        return false;
    }
}

function is_maintenance_mode(): bool {
    $v = get_setting_value('maintenance_mode');
    if ($v === null) return false;
    return ($v === '1' || strtolower($v) === 'true');
}
