<?php
	session_start();
	
	include_once("config.php");
	
	if(@$_SESSION["key"] != SECRET_KEY){
		returnError("error");
	}
	
	function returnJSON($array){
		echo json_encode($array);
		exit();
	}
	
	function returnError($text){
		returnJSON(array("error" => $text));
	}
	
	function returnSuccess(){
		returnJSON(array("success" => true));
	}
	
	try{
		$db = new PDO("mysql:host=".SQL_HOST.";dbname=".SQL_BASE,SQL_USER,SQL_PASS);
	}
	catch(Exception $e){
		returnError("Database connection error : ".$e->getMessage());
	}
	$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
	$db->exec("SET NAMES 'utf8'");
	
	function DBQuery($req,$args){
		try{
			$req->execute($args);
		}catch(Exception $e){
			returnError("Erreur de base de donnée: ".$e->getMessage());
		}
	}
	
	if(isset($_GET["action"])){
		$_POST = $_GET;
	}
	
	function taskBot(){
		global $db;
		
		$req = $db->prepare("SELECT * FROM Exports
			JOIN Formats ON ID_Format = ID_Format_Export
			WHERE Running_Export = 1");
		DBQuery($req,array());
		
		foreach($req->fetchAll() as $d){
			$statusfile = file("export/".$d["Name_Export"]."-status.txt",FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
			if($statusfile[count($statusfile)-1] == "Blender quit"){
				// Export finished
				$name = uniqid("o").".".$d["Extension_Format"];
				
				$db->beginTransaction();
				$req = $db->prepare("INSERT INTO Outputs(ID_Export_Output, File_Output, DateCreate_Output)
					VALUES (:export, :name, NOW())");
				DBQuery($req,array(
					"export" => $d["ID_Export"],
					"name" => $name
				));
				
				$req = $db->prepare("UPDATE Exports SET Running_Export = 0 WHERE ID_Export = :export");
				DBQuery($req,array(
					"export" => $d["ID_Export"]
				));
				
				if(!copy("export/".$d["Name_Export"].".".$d["Extension_Format"],"output/".$name)){
					returnError("Erreur lors de la copie");
				}
				
				$db->commit();
			}
		}
	}
	
	switch(@$_POST["action"]){
		case "filelist":
			taskBot();
			
			$req = $db->prepare("SELECT * FROM Files
				LEFT JOIN Exports ON ID_File_Export = ID_File
				LEFT JOIN Formats ON ID_Format = ID_Format_Export
				LEFT JOIN Outputs ON ID_Export_Output = ID_Export
				ORDER BY DateCreate_File DESC, DateStart_Export DESC, DateCreate_Output DESC");
			DBQuery($req,array());
			
			$files = array();
			
			$lastfile = 0;
			$lastexport = 0;
			
			foreach($req->fetchAll() as $d){
				if($d["ID_File"] != $lastfile){
					$files[] = array(
						"id" => intval($d["ID_File"]),
						"title" => $d["Title_File"],
						"date" => $d["DateCreate_File"],
						"exports" => array()
					);
					$lastfile = $d["ID_File"];
				}
				if($d["ID_Export"] != NULL){
					if($d["ID_Export"] != $lastexport){
						$frame = -1;
						if($d["Running_Export"] == 1){
							$statusfile = file("export/".$d["Name_Export"]."-status.txt",FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
							if(count($statusfile) > 0 && $statusfile[count($statusfile)-1] != "Blender quit"){
								for($i=count($statusfile)-1;$i>0;--$i){
									if(preg_match("/^Fra:([0-9]+) .*$/",$statusfile[$i],$match)){
										$frame = $match[1];
										break;
									}
								}
							}
						}
						$files[count($files)-1]["exports"][] = array(
							"id" => intval($d["ID_Export"]),
							"startframe" => intval($d["StartFrame_Export"]),
							"endframe" => intval($d["EndFrame_Export"]),
							"currentframe" => intval($frame),
							"running" => $d["Running_Export"] == 1,
							"outputs" => array()
						);
						$lastexport = $d["ID_Export"];
					}
					
					if($d["ID_Output"] != NULL){
						$indlastfile = count($files)-1;
						$indlastexport = count($files[$indlastfile]["exports"])-1;
						$files[$indlastfile]["exports"][$indlastexport]["outputs"][] = array(
							"id" => intval($d["ID_Output"]),
							"file" => $d["File_Output"],
							"date" => $d["DateCreate_Output"]
						);
					}
				}
			}
			
			returnJSON(array("files" => $files));
		break;
		case "upload":
			$name = uniqid("u");
			$db->beginTransaction();
			$req = $db->prepare("INSERT INTO Files(Title_File, Name_File, DateCreate_File)
				VALUES (:title, :name, NOW())");
			DBQuery($req,array(
				"title" => $_POST["title"],
				"name" => $name
			));
			$filename = $name.".blend";
			if(file_put_contents(
				"upload/".$filename,
				file_get_contents('php://input')
			)){
				$db->commit();
			}else{
				$db->rollback();
				returnError("Impossible de créer le fichier sur le serveur");
			}
			returnSuccess();
		break;
		case "formats":
			$req = $db->prepare("SELECT * FROM Formats
				ORDER BY Name_Format");
			DBQuery($req,array());
			
			$list = array();
			
			foreach($req->fetchAll() as $d){
				$list[] = array(
					"id" => intval($d["ID_Format"]),
					"name" => $d["Name_Format"],
					"ext" => $d["Extension_Format"]
				);
			}
			
			returnJSON(array("formats" => $list));
		break;
		case "export":
			$name = uniqid("e");
			
			$req = $db->prepare("SELECT * FROM Files WHERE ID_File = :file");
			DBQuery($req,array(
				"file" => $_POST["file"]
			));
			$DataFile = $req->fetch();
			
			$req = $db->prepare("SELECT * FROM Formats WHERE ID_Format = :format");
			DBQuery($req,array(
				"format" => $_POST["format"]
			));
			$DataFormat = $req->fetch();
			
			$db->beginTransaction();
			$req = $db->prepare("INSERT INTO Exports(ID_File_Export, ID_Format_Export, Name_Export, DateStart_Export, Running_Export)
				VALUES (:file, :format, :name, NOW(), 1)");
			DBQuery($req,array(
				"file" => $_POST["file"],
				"format" => $_POST["format"],
				"name" => $name
			));
			
			//exec("blender/blender -b upload/".$DataFile["Name_File"].".blend -o export/".$name.".".$DataFormat["Extension_Format"]." -e 5 -a > export/".$name."-status.txt &",$res);
			exec("blender/blender -b upload/".$DataFile["Name_File"].".blend -o export/".$name.".".$DataFormat["Extension_Format"]." -a > export/".$name."-status.txt &",$res);
			
			$db->commit();
			
			returnSuccess();
		break;
		case "taskbot":
			taskBot();
			returnSuccess();
		break;
		case "runningexports":
			$req = $db->prepare("SELECT * FROM Exports
				JOIN Files ON ID_File = ID_File_Export
				JOIN Formats ON ID_Format = ID_Format_Export
				WHERE Running_Export = 1");
			DBQuery($req,array());
			
			$list = array();
			
			foreach($req->fetchAll() as $d){
				$statusfile = file("export/".$d["Name_Export"]."-status.txt",FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
				$frame = -1;
				if($statusfile[count($statusfile)-1] != "Blender quit"){
					for($i=count($statusfile)-1;$i>0;--$i){
						if(preg_match("/^Fra:([0-9]+) .*$/",$statusfile[$i],$match)){
							$frame = $match[1];
							break;
						}
					}
				}
				$list[] = array(
					"id" => intval($d["ID_Export"]),
					"frame" => $frame == -1 ? "end" : intval($frame)
				);
			}
			
			returnJSON(array("exports" => $list));
		break;
		case "delfile":
			taskBot();
			$req = $db->prepare("SELECT * FROM Files
				JOIN Exports ON ID_File_Export = ID_File
				WHERE ID_File = :file");
			DBQuery($req,array(
				"file" => $_POST["id"]
			));
			$finished = true;
			foreach($req->fetchAll() as $d){
				if($d["Running_Export"] == 1){
					$finished = false;
					break;
				}
			}
			
			if(!$finished){
				returnError("stillrunning");
			}
			
			$req = $db->prepare("DELETE FROM Files
				WHERE ID_File = :file");
			DBQuery($req,array(
				"file" => $_POST["id"]
			));
			
			returnSuccess();
		break;
		case "delexport":
			taskBot();
			$req = $db->prepare("SELECT * FROM Exports
				WHERE ID_Export = :export");
			DBQuery($req,array(
				"export" => $_POST["id"]
			));
			$finished = true;
			foreach($req->fetchAll() as $d){
				if($d["Running_Export"] == 1){
					$finished = false;
					break;
				}
			}
			
			if(!$finished){
				returnError("stillrunning");
			}
			
			$req = $db->prepare("DELETE FROM Exports
				WHERE ID_Export = :export");
			DBQuery($req,array(
				"export" => $_POST["id"]
			));
			
			returnSuccess();
		break;
		default:
			returnError("noaction");
	}
?>