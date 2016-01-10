<?php
namespace App\Http\Controllers;

use Log;
use Mail;

class DBbackup
{
    private $user;
    private $pass;
    private $dbname;
    private $host;

    private $backupEmail;
    private $emailSubject;

    private $deleteBackupFile;

    function __construct($host, $user, $pass, $dbname, $backupEmail, $emailSubject, $deleteBackupFile = false)
    {
        //database details
        $this->host = $host;
        $this->user = $user;
        $this->pass = $pass;
        $this->dbname = $dbname;
        //#database details
        $this->deleteBackupFile = $deleteBackupFile; # delete backup from local
        //Email settings
        $this->backupEmail = $backupEmail; #mention which email receive the backup db
        $this->emailSubject = $emailSubject; #mention the email subject

        $this->backup_tables($this->host, $this->user, $this->pass, $this->dbname);
    }

    function backup_tables($host, $user, $pass, $name, $tables = '*')
    {
        //name is db name
        $link = mysqli_connect($host, $user, $pass, $name);
        //mysql_select_db($name, $link);
        mysqli_query($link, "SET NAMES 'utf8'");

        //get all of the tables
        if($tables == '*') {
            $tables = array();
            $result = mysqli_query($link, 'SHOW TABLES');
            while ($row = mysqli_fetch_row($result)) {
                $tables[] = $row[0];
            }
        }
        else {
            $tables = is_array($tables) ? $tables : explode(',', $tables);
        }
        $return = '';

        //cycle through
        foreach ($tables as $table) {
            $result = mysqli_query($link, 'SELECT * FROM ' . $table);
            $num_fields = mysqli_num_fields($result);

            $return .= 'DROP TABLE ' . $table . ';';
            $row2 = mysqli_fetch_row(mysqli_query($link, 'SHOW CREATE TABLE ' . $table));
            $return .= "\n\n" . $row2[1] . ";\n\n";

            for ($i = 0; $i < $num_fields; $i++) {
                while ($row = mysqli_fetch_row($result)) {
                    $return .= 'INSERT INTO ' . $table . ' VALUES(';
                    for ($j = 0; $j < $num_fields; $j++) {
                        $row[$j] = addslashes($row[$j]);
                        $row[$j] = str_replace("\n", "\\n", $row[$j]);
                        if(isset($row[$j])) {
                            $return .= '"' . $row[$j] . '"';
                        }
                        else {
                            $return .= '""';
                        }
                        if($j < ($num_fields - 1)) {
                            $return .= ',';
                        }
                    }
                    $return .= ");\n";
                }
            }
            $return .= "\n\n\n";
        }

        //save file
        $fileName = 'db-backup-' . date('d-m-Y') . '.sql';
        $handle = fopen(storage_path('app/' . $fileName), 'w+');
        fwrite($handle, $return);
        fclose($handle);

        $this->emailDatabase($fileName);

        if($this->deleteBackupFile) {
            unlink(storage_path('app/' . $fileName));
        }

    }

    // backup database receiver
    public function emailDatabase($fileName)
    {
        try {
            Mail::raw('This is automatic database backup to your email.', function ($message) use ($fileName) {
                $message->to($this->backupEmail, '');
                $message->attach(storage_path('app/' . $fileName));
                $message->subject($this->emailSubject);
            });
            Log::info('DB backup Mail send success');
        } catch (\Exception $e) {
            Log::info('DB backup Mail send fail');
        }
    }
}
