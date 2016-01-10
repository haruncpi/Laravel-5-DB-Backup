<?php namespace App\Http\Controllers;

use App\Http\Requests;
use App\Http\Controllers\Controller;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;

class ScheduleController extends Controller
{

    public function databaseBackup()
    {
        # database details
        $host = Config::get('database.connections.mysql.host'); # your database host
        $user = Config::get('database.connections.mysql.username'); # your database username
        $pass = Config::get('database.connections.mysql.password'); # your database password
        $dbname = Config::get('database.connections.mysql.database');# database name

        # database details
        $deleteBackupFile = false; # delete backup from local
        # Email settings
        $backupEmail = 'harun.cox@gmail.com'; #mention which email receive the backup db
        $emailSubject = 'ASL Branding | Backup-' . date('d-m-Y'); #mention the email subject

        $backup = new DBbackup($host, $user, $pass, $dbname, $backupEmail, $emailSubject, $deleteBackupFile);
    }
}
