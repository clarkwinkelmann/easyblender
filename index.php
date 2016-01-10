<?php
	session_start();
	if(isset($_GET["k"])){
		$_SESSION["key"] = $_GET["k"];
	}
?>
<!doctype html>
<html lang="fr"><head>
	<meta charset="utf-8"/>
	<title>Easy Blender</title>
	<script type="text/javascript" src="http://code.jquery.com/jquery-2.1.0.min.js"></script>
<style type="text/css">

body{
	font-family: Arial, sans-serif;
	background: #F8F8F8;
}

h1{
	text-align: center;
}

.files{
	width: 1000px;
	margin: 0 auto;
	margin-bottom: 50px;
}

.files .file{
	background: #FFFFFF;
	box-shadow: 1px 1px 2px rgba(0,0,0,0.2);
	border: 1px solid #DDD;
	padding: 20px;
	margin: 5px;
}

.files .file > .head{
	margin-bottom: 10px;
	border-bottom: 2px solid #EEE;
}

.files .file > .head .title{
	font-size: 1.5em;
}

.files .file > .head .date{
	color: #666;
	margin-left: 2em;
}

.files .export{
	background: #6A8547;
	box-shadow: 1px 1px 2px rgba(0,0,0,0.3);
	margin: 5px 0;
	padding: 10px 20px;
	color: #FFF;
}

.files .export .status{
	font-weight: bold;
	margin: 5px 0;
}

.files .output{
	background: #85A659;
	border-left: 10px solid rgba(248, 248, 248, 0.1);
	padding: 10px;
	margin-top: 10px;
	box-shadow: 1px 1px 2px rgba(0,0,0,0.2);
}

.files .close{
	float: right;
	color: #BBB;
	font-size: 30px;
	font-weight: bold;
	line-height: 0;
	cursor: pointer;
}

.files .close:hover{
	color: #888;
}

.files .export .close{
	color: #FFF;
	line-height: 20px;
}

.files .export .close:hover{
	color: #DDD;
}

</style>
</head><body>
<h1>Easy Blender <button onclick="refresh()">Refresh</button> <select id="autorefreshinterval">
	<option value="5">5 secondes</option>
	<option value="10">10 secondes</option>
	<option value="30">30 secondes</option>
	<option value="60">1 minute</option>
	<option value="600" selected>10 minutes</option>
	<option value="1800">30 minutes</option>
	<option value="3600">1 heure</option>
</select></h1>
<div class="files"><div class="file">
	<div class="head"><div class="title">Envoyer un fichier</div></div>
	<div><input type="file" id="upload"></div>
</div></div>
<div id="filelist" class="files"></div>
<!--<div id="exportslist"></div>-->
<!--<button onclick="runBot()">BOT</button>-->

<script type="text/javascript">

function exportFile(target,id){
	$('#exportform').remove();
	
	var $form = $('<form action="#" method="post" onsubmit="startExport(event)" id="exportform" style="display: none;">'+
		'<select id="exportformat"></select>'+
		'<input type="submit" value="Exporter">'+
	'</form>');
	target.after($form);

	var i, d;
	for(i in ExportFormats){
		d = ExportFormats[i];
		$('#exportformat').append('<option value="'+d.id+'">'+d.name+' (.'+d.ext+')</option>');
	}
	
	$('#exportform').slideDown();
	
	$form.on('submit',function(e){
		e.preventDefault();
		
		$.post('query.php',{
			action: 'export',
			file: id,
			format: $('#exportformat').val()
		},function(data){
			$('#exportform').slideUp();
		},'json');
	});
}

function startExport(e){
	e.preventDefault();
	
	$.post('query.php',{
		action: 'export',
		file: $('#exportfileid').val(),
		format: $('#exportformat').val()
	},function(data){
		$('#exportform').slideUp();
	},'json');
}

var ExportFormats = [];

$.post('query.php',{
	action: 'formats'
},function(data){
	$('#exportformat *').remove();

	var i, d;
	for(i in data.formats){
		d = data.formats[i];
		ExportFormats.push({
			id: d.id,
			name: d.name,
			ext: d.ext
		});
	}
},'json');

function refreshFiles(){
	$.post('query.php',{
		action: 'filelist'
	},function(data){
		$('#filelist *').remove();

		var i, j, k, dfile, dexp, dout, $file, $export, $delfilebtn, $expfilebtn, $delexpbtn;
		for(i in data.files){
			dfile = data.files[i];
			$file = $('<div class="file"></div>');
			
			$delfilebtn = $('<div class="close">&times;</div>');
			$delfilebtn.click({id: dfile.id, title: dfile.title},function(e){
				if(confirm('Supprimer le fichier "'+e.data.title+'" ?')){
					$.post('query.php',{
						action: 'delfile',
						id: e.data.id
					},function(){
						refresh();
					});
				}
			});
			$file.append($delfilebtn);
			
			$file.append('<div class="head"><span class="title">'+dfile.title+
				'</span><span class="date">Ajouté '+dfile.date+'</span></div>');
			
			$expfilebtn = $('<button onclick="exportFile('+dfile.id+',\''+dfile.title+'\')">Exporter</button>');
			$expfilebtn.click({id: dfile.id},function(e){
				exportFile($(this),e.data.id)
			});
			$file.append($expfilebtn);
			
			$('#filelist').append($file);
			
			for(j in dfile.exports){
				dexp = dfile.exports[j];
				
				$export = $('<div class="export"></div>');
				
				$delfilebtn = $('<div class="close">&times;</div>');
				$delfilebtn.click({id: dexp.id},function(e){
					if(confirm('Supprimer cet export ?')){
						$.post('query.php',{
							action: 'delexport',
							id: e.data.id
						},function(){
							refresh();
						});
					}
				});
				$export.append($delfilebtn);
				
				//console.log(dexp.id,dexp.currentframe);
				$export.append(//'<div class="details">Frames '+dexp.startframe+' à '+dexp.endframe+'</div>'+
					'<div class="status">État: '+((!dexp.running || dexp.currentframe == -1) ? 'Terminé' : 'En cours, traitement de la frame '+dexp.currentframe)+'</div>');
				$file.append($export);
				
				for(k in dexp.outputs){
					dout = dexp.outputs[k];
					
					$export.append('<div class="output">'+dout.file+' <a href="output/'+dout.file+'" target="_blank">Télécharger</a></div>');
				}
			}
		}
	},'json');
}

/*function refreshExports(){
	$.post('query.php',{
		action: 'runningexports'
	},function(data){
		$('#exportslist *').remove();

		var i, d;
		for(i in data.exports){
			d = data.exports[i];
			$('#exportslist').append('<div class="export">#'+d.id+
				' '+(d.frame == 'end' ? 'Terminé' : 'Frame '+d.frame));
		}
	},'json');
}*/

function refresh(){
	refreshFiles();
	//refreshExports();
}

refresh();

function upload(e){
	var files = e.target.files || e.dataTransfer.files;
	var file = files[0];
	var xhr = new XMLHttpRequest();
	if(xhr.upload){
		if(file.type == "application/x-blender"){
			xhr.open("POST", 'query.php?action=upload&title='+file.name);
			xhr.send(file);
			xhr.onreadystatechange = function(aEvt){
				if(xhr.readyState == 4){
					if(xhr.status == 200){
						var data = $.parseJSON(xhr.responseText);
						if(data.error){
							alert(data.error);
							return;
						}
						refresh();
					}else{
						alert('Ereur durant l\'envoi de l\'image');
					}
				}
			};
		}else{
			alert('Veuillez choisir un fichier Blender');
		}
	}else{
		alert('Votre navigateur ne supporte pas notre méthode d\'envoi d\'images. Veuillez utiliser la dernière version de Chrome ou Firefox');
	}
}

$('#upload').get(0).addEventListener('change',upload,false);

/*function runBot(){
	$.post('query.php',{
		action: 'taskbot'
	},function(data){
		refresh();
	},'json');
}*/

function autoRefresh(){
	refresh();
	setTimeout(autoRefresh,$('#autorefreshinterval').val()*1000);
}

autoRefresh();

</script>
</body></html>