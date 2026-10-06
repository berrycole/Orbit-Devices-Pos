<?php
namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateServerlessStorage extends Migration
{
    public function up()
    {
        $sessions = $this->db->prefixTable('ci_sessions');
        $media = $this->db->prefixTable('media');

        if ($this->db->DBDriver === 'SQLite3') {
            $this->db->query("CREATE TABLE IF NOT EXISTS {$sessions} (id VARCHAR(128) PRIMARY KEY, ip_address VARCHAR(45) NOT NULL, timestamp DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP, data BLOB NOT NULL)");
            $this->db->query("CREATE INDEX IF NOT EXISTS {$sessions}_timestamp ON {$sessions} (timestamp)");
            $this->db->query("CREATE TABLE IF NOT EXISTS {$media} (name VARCHAR(40) PRIMARY KEY, mime VARCHAR(20) NOT NULL, data TEXT NOT NULL)");
            return;
        }

        $this->db->query("CREATE TABLE IF NOT EXISTS {$sessions} (id VARCHAR(128) NOT NULL PRIMARY KEY, ip_address VARCHAR(45) NOT NULL, timestamp TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP, data BLOB NOT NULL, INDEX ci_sessions_timestamp (timestamp))");
        $this->db->query("CREATE TABLE IF NOT EXISTS {$media} (name VARCHAR(40) NOT NULL PRIMARY KEY, mime VARCHAR(20) NOT NULL, data MEDIUMTEXT NOT NULL)");
    }

    public function down()
    {
        $this->forge->dropTable('media', true);
        $this->forge->dropTable('ci_sessions', true);
    }
}
