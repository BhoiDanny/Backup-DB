<?php
   # HOW TO BACK UP DATA FROM MYSQL DB's
   #make connection to db
   $host = 'localhost';
   $user = 'root';
   $pass = 'pass';
   $dbname = 'sannytech';
   $port = 3306;
   $options = array(
      PDO::ATTR_PERSISTENT => true,
      PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
      PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
      PDO::ATTR_EMULATE_PREPARES => false
   );

   $dsn = "mysql:host=$host;port=$port;dbname=$dbname";

   try {
      $db = new PDO($dsn, $user, $pass,$options);
   } catch (PDOException $e) {
      echo $e->getMessage();
   }

   #backup database function
   function backup($filename, $tablePick = false, $dbTable = []) {
      global $db, $host;
      # Get all tables
      $tables = $db->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN);
      if($dbTable)
         $tables = array_intersect($tables, $dbTable);

      #Prepare SQL script
      $sql = '-- Database Backup --' . PHP_EOL . PHP_EOL;
      $sql .= '-- -----------------------------'. PHP_EOL . PHP_EOL;
      $sql .= '-- Host ' . $host . PHP_EOL;
      $sql .= '-- Generation Time: ' . date('M j, Y \a\t g:i A');
      $sql .= '-- -----------------------------'. PHP_EOL . PHP_EOL;

      #Cycle through Tables
      foreach($tables as $table) {
         $sql .= 'DROP TABLE IF EXISTS `' . $table . '`;';

         #Create the tables structure
         $create = $db->query("SHOW CREATE TABLE " .$table)->fetch();
         $sql .= "\n\n" . $create['Create Table'] . ";\n\n";

         #Get the data from tables
         $data = $db->query('SELECT * FROM ' .$table)->fetchAll();

         #Cycle Through the tables data
         foreach($data as $row) {
            #Prepare Statements for Inserts

            $sql .= 'INSERT INTO `'.$table. '` VALUES(';

            #Cycle through each field
            foreach($row as $value) {
               #add the field value
               $value = addslashes($value);

               #Escape Every apostrophe
               $value = str_replace("\n", "\\n",$value);
               if(!isset($value)) {
                  $sql .= "''";
               } else {
                  $sql .= "'" . $value . "'";
               }
               $sql .= ',';
            }

            #remove the last comma
            $sql = substr($sql, 0,-1);
            $sql .= ");\n";

         }


         #Add a new line
         $sql .= "\n\n";
         $sql .= '-- -----------------------------'. PHP_EOL . PHP_EOL;
         $sql .= '-- End of data for table `'. $table .'`' . PHP_EOL;
         $sql .= '-- -----------------------------'. PHP_EOL . PHP_EOL;

      }

      #Place Statements into a file

      $write = file_put_contents($filename.'.sql',$sql);
      return (bool)$write;

   }

   if(backup('sannytechBackup')){
      echo "Successful";
   } else {
      echo "Failed";
   }
