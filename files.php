<?php
// ====== НАСТРОЙКИ ======
$baseDir = __DIR__ . '/Файлообменник';

// Определяем текущий путь
$subDir = isset($_GET['dir']) ? $_GET['dir'] : '';
$subDir = trim(str_replace('..', '', $subDir), '/');
$currentDir = realpath($baseDir . '/' . $subDir);
if (strpos($currentDir, realpath($baseDir)) !== 0) {
    die('Недопустимый путь');
}

// ====== СОЗДАНИЕ ПАПКИ ======
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['new_folder'])) {
    $newFolder = basename(trim($_POST['new_folder']));
    if ($newFolder !== '') {
        @mkdir($currentDir . '/' . $newFolder, 0777, true);
    }
    header("Location: files.php?dir=" . urlencode($subDir));
    exit;
}

// ====== ЗАГРУЗКА ФАЙЛА ======
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['file'])) {
    $upload = $_FILES['file'];
    if ($upload['error'] === UPLOAD_ERR_OK) {
        $name = basename($upload['name']);
        move_uploaded_file($upload['tmp_name'], "$currentDir/$name");
        exit('OK'); // ответ для AJAX
    } else {
        exit('ERROR');
    }
}

// ====== СПИСОК ======
$entries = array_diff(scandir($currentDir), ['.', '..']);
?>
<!DOCTYPE html>
<html lang="ru">
<head>
  <meta charset="utf-8">
  <title>Файлообменник</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <style>
    body {
  margin: 0;
  background: url('1.jpg') no-repeat center center fixed;
  background-size: cover;
  color: #ffff00;
  font-family: "Open Sans", sans-serif;
  text-align: center;
  overflow-y: auto;      /* ✅ разрешаем вертикальную прокрутку */
  overflow-x: hidden;    /* оставляем без горизонтальной прокрутки */
  min-height: 100vh;     /* чтобы фон покрывал всё окно */
    }
    body::before {
      content: "";
      position: fixed;
      top: 0; left: 0;
      width: 100%; height: 100%;
      background: rgba(0,0,0,0.4);
      z-index: -2;
    }
    #snow-canvas {
      position: fixed;
      top: 0; left: 0;
      width: 100%; height: 100%;
      pointer-events: none;
      z-index: -1;
    }
    h1 {
      color: #ff0000;
      font-size: 50px;
      margin-top: 40px;
      text-shadow: 2px 2px 6px black;
    }
    table {
      margin: 40px auto;
      border-collapse: collapse;
      width: 80%;
      background: rgba(0,0,0,0.5);
      border-radius: 10px;
    }
    th, td {
      padding: 12px;
      color: #00ffff;
      font-size: 18px;
      border-bottom: 1px solid #00ffff33;
    }
    a {
      color: #00ffff;
      text-decoration: none;
      text-shadow: 0 0 6px #00ffff, 0 0 12px #00ffff44;
      transition: all 0.3s ease;
    }
    a:hover {
      color: #aaffff;
      text-shadow: 0 0 8px #00ffff, 0 0 16px #00ffff;
    }
    footer {
      margin-top: 40px;
      font-size: 20px;
      color: #ffff66;
      text-shadow: 1px 1px 4px black;
    }
    button, input[type="submit"] {
      background: none;
      border: 1px solid #00ffff;
      color: #00ffff;
      padding: 8px 16px;
      border-radius: 8px;
      cursor: pointer;
      text-shadow: 0 0 6px #00ffff;
      transition: all 0.3s ease;
    }
    button:hover, input[type="submit"]:hover {
      background: #00ffff33;
      transform: scale(1.05);
    }
    #progress-container {
      width: 80%;
      margin: 20px auto;
      height: 20px;
      background-color: #333;
      border-radius: 10px;
      overflow: hidden;
      display: none;
    }
    #progress-bar {
      height: 100%;
      width: 0%;
      background-color: #00ffff;
      transition: width 0.1s linear;
    }
    #status { color: #00ffff; margin-top: 10px; }
    .nav {
      margin-top: 10px;
      font-size: 20px;
      color: #ffffaa;
    }
    .folder-icon { color: #ffaa00; }
  </style>
</head>
<body>
<canvas id="snow-canvas"></canvas>

<h1>Файлообменник</h1>

<div class="nav">
  📂 Путь: 
  <a href="files.php">Главная</a>
  <?php
    $parts = explode('/', $subDir);
    $path = '';
    foreach ($parts as $p) {
        if ($p === '') continue;
        $path .= ($path ? '/' : '') . $p;
        echo ' / <a href="?dir=' . urlencode($path) . '">' . htmlspecialchars($p) . '</a>';
    }
  ?>
</div>

<!-- Форма загрузки -->
<form id="upload-form">
  <input type="file" name="file" id="file" required>
  <button type="submit">Загрузить</button>
</form>

<div id="progress-container"><div id="progress-bar"></div></div>
<div id="status"></div>

<!-- Создание папки -->
<form method="post" style="margin-top:20px;">
  <input type="text" name="new_folder" placeholder="Имя новой папки" required>
  <input type="submit" value="Создать папку">
</form>

<table>
  <tr><th>Имя</th><th>Размер</th><th>Действие</th></tr>
  <?php foreach ($entries as $e):
    $path = "$currentDir/$e";
    $isDir = is_dir($path);
    if ($isDir) {
        echo '<tr><td class="folder-icon">📁 <a href="?dir=' . urlencode(trim($subDir . '/' . $e, '/')) . '">' . htmlspecialchars($e) . '</a></td><td>-</td><td>-</td></tr>';
    }
  endforeach;
  foreach ($entries as $e):
    $path = "$currentDir/$e";
    if (!is_file($path)) continue;
    $size = round(filesize($path) / 1024, 2) . ' КБ';
  ?>
  <tr>
    <td>📄 <?= htmlspecialchars($e) ?></td>
    <td><?= $size ?></td>
    <td><a href="<?= 'Файлообменник/' . ($subDir ? rawurlencode($subDir) . '/' : '') . rawurlencode($e) ?>" download>Скачать</a></td>
  </tr>
  <?php endforeach; ?>
</table>

<footer><a href="index.html">⬅ Вернуться на главную</a></footer>

<script>
  // ❄️ Снег
  const canvas=document.getElementById('snow-canvas');const ctx=canvas.getContext('2d');
  let w,h,snow=[];function resize(){w=canvas.width=innerWidth;h=canvas.height=innerHeight;}
  addEventListener('resize',resize);resize();
  function make(n){snow=[];for(let i=0;i<n;i++)snow.push({x:Math.random()*w,y:Math.random()*h,r:Math.random()*4+1,d:Math.random()*1+0.5});}
  make(150);let a=0;function draw(){ctx.clearRect(0,0,w,h);ctx.fillStyle="white";ctx.beginPath();
    for(let f of snow){ctx.moveTo(f.x,f.y);ctx.arc(f.x,f.y,f.r,0,Math.PI*2);}ctx.fill();a+=0.01;
    for(let f of snow){f.y+=Math.pow(f.d,2)+1;f.x+=Math.sin(a)*0.5;if(f.y>h){f.y=0;f.x=Math.random()*w;}}
    requestAnimationFrame(draw);}draw();

  // 📤 Загрузка с прогрессом
  const form=document.getElementById('upload-form');
  const fileInput=document.getElementById('file');
  const bar=document.getElementById('progress-bar');
  const cont=document.getElementById('progress-container');
  const status=document.getElementById('status');

  form.addEventListener('submit',e=>{
    e.preventDefault();
    const f=fileInput.files[0]; if(!f) return;
    const xhr=new XMLHttpRequest(); const fd=new FormData();
    fd.append('file',f);
    xhr.upload.addEventListener('loadstart',()=>{cont.style.display='block';bar.style.width='0%';status.textContent='Начало загрузки...';});
    xhr.upload.addEventListener('progress',ev=>{
      if(ev.lengthComputable){
        const p=(ev.loaded/ev.total)*100;
        bar.style.width=p.toFixed(1)+'%';
        status.textContent=`Загружено ${p.toFixed(1)}% (${(ev.loaded/1024/1024).toFixed(1)} МБ из ${(ev.total/1024/1024).toFixed(1)} МБ)`;
      }
    });
    xhr.upload.addEventListener('load',()=>{bar.style.width='100%';status.textContent='Файл загружен! Обновите страницу.';});
    xhr.open('POST','files.php?dir='+encodeURIComponent('<?php echo $subDir; ?>'));
    xhr.send(fd);
  });
</script>

</body>
</html>
