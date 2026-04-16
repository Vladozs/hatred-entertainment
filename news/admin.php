<?php
session_start();

$token = $_GET['token'] ?? '';
if (empty($_SESSION['admin_logged_in']) || empty($_SESSION['admin_token']) || $_SESSION['admin_token'] !== $token) {
    header('Location: /news/newsadmin.html');
    exit;
}
?>
<!doctype html>
<html lang="ru">
<head>
<meta charset="utf-8"/>
<meta name="viewport" content="width=device-width,initial-scale=1"/>
<title>Админка - HATRED</title>
<style>
  body{background:#000;color:#fff;font-family:Inter,Arial;padding:20px;}
  .wrap{max-width:1100px;margin:0 auto;}
  .field{margin-bottom:12px;}
  input, textarea, select{width:100%;padding:8px;border-radius:6px;background:#111;border:1px solid #222;color:#fff;}
  .btn{padding:10px 14px;background:#c50000;border-radius:8px;color:#fff;border:none;cursor:pointer;}
  .small{font-size:0.9rem;color:#c5c5c5;}
</style>
</head>
<body>
<div class="wrap">
  <h1>Админ - публикация новостей</h1>
  <p class="small">Вы вошли как: <?php echo htmlspecialchars($_SESSION['admin_login']); ?></p>

  <section style="margin-top:20px;">
    <h2>Создать / отредактировать новость</h2>
    <form id="articleForm">
      <div class="field">
        <label>Заголовок</label>
        <input name="title" required />
      </div>
      <div class="field">
        <label>Слаг (часть URL, латиница и дефисы)</label>
        <input name="slug" placeholder="например release-eveline" required />
      </div>
      <div class="field">
        <label>Дата (ISO)</label>
        <input name="date" value="<?php echo date('c'); ?>" />
      </div>
      <div class="field">
        <label>Тег</label>
        <select name="tag">
          <option>Игровые обновления</option>
          <option>Релиз</option>
          <option>Анонс</option>
          <option>Прочее</option>
        </select>
      </div>
      <div class="field">
        <label>Короткое описание / анонс</label>
        <textarea name="excerpt" rows="3"></textarea>
      </div>
      <div class="field">
        <label>Превью (загрузить файл)</label>
        <input type="file" id="previewFile" accept="image/*" />
        <div id="previewUploaded" class="small"></div>
      </div>
      <div class="field">
        <label>Содержимое новости (HTML допустим)</label>
        <textarea id="content" name="content" rows="10" placeholder="Вы можете вставлять теги &lt;img src=&quot;/news/uploads/xxx.jpg&quot;&gt;"></textarea>
        <div class="small">Можно вставлять &lt;img src="/news/uploads/..."&gt; - используйте кнопку загрузить изображение ниже.</div>
      </div>

      <div class="field">
        <label>Загрузить картинку внутрь текста</label>
        <input type="file" id="inlineImage" accept="image/*" />
        <div id="uploadResult" class="small"></div>
      </div>

      <div style="margin-top:10px;">
        <button class="btn" type="button" id="saveBtn">Сохранить и опубликовать</button>
      </div>
    </form>
  </section>

  <section style="margin-top:30px;">
    <h2>Управление существующими новостями</h2>
    <div id="existingList" class="small">Загрузка...</div>
  </section>

</div>

<script>
async function loadList(){
  const r = await fetch('/news/news.json'); 
  const j = await r.json();
  const el = document.getElementById('existingList');
  if(!j.articles || !j.articles.length){ el.innerHTML = '<p>Пока нет новостей</p>'; return; }
  j.articles.sort((a,b)=> new Date(b.date)-new Date(a.date));
  el.innerHTML = '';
  j.articles.forEach(a=>{
    const div = document.createElement('div');
    div.innerHTML = `<div style="padding:8px;border-bottom:1px solid #222;">
      <strong>${a.title}</strong> - ${new Date(a.date).toLocaleString()} - <em>${a.tag}</em>
      <div style="margin-top:6px;">
        <button class="btn" onclick="edit('${a.slug}')">Редактировать</button>
        <button class="btn" style="background:#444;margin-left:8px;" onclick="del('${a.slug}')">Удалить</button>
      </div>
    </div>`;
    el.appendChild(div);
  });
}
function edit(slug){
  fetch('/news/news.json').then(r=>r.json()).then(j=>{
    const art = j.articles.find(x=>x.slug===slug);
    if(!art) return alert('Не найдено');
    document.querySelector('[name=title]').value = art.title || '';
    document.querySelector('[name=slug]').value = art.slug || '';
    document.querySelector('[name=date]').value = art.date || '';
    document.querySelector('[name=excerpt]').value = art.excerpt || '';
    document.querySelector('[name=content]').value = art.content || '';
    document.querySelector('[name=tag]').value = art.tag || 'Прочее';
  });
}

async function del(slug){
  if(!confirm('Удалить новость?')) return;
  const r = await fetch('/news/save.php', { method:'POST', body: new URLSearchParams({ action:'delete', slug })});
  const j = await r.json();
  alert(j.message || (j.error || 'done'));
  loadList();
}

document.getElementById('previewFile').addEventListener('change', async (e)=>{
  const f = e.target.files[0];
  if(!f) return;
  const fd = new FormData();
  fd.append('file', f);
  fd.append('purpose','preview');
  const r = await fetch('/news/upload.php', { method:'POST', body:fd });
  const j = await r.json();
  if(j.success){ document.getElementById('previewUploaded').textContent = 'Превью загружено: ' + j.url; document.querySelector('[name=content]').value += `\n<!-- preview:${j.url} -->\n`; }
  else alert(j.error||'Ошибка загрузки');
});

document.getElementById('inlineImage').addEventListener('change', async (e)=>{
  const f = e.target.files[0]; if(!f) return;
  const fd = new FormData(); fd.append('file', f); fd.append('purpose','inline');
  const r = await fetch('/news/upload.php', { method:'POST', body:fd });
  const j = await r.json();
  if(j.success){ document.getElementById('uploadResult').innerHTML = 'URL: ' + j.url + '<br>Вставьте <code>&lt;img src="'+j.url+'"&gt;</code> в текст.'; }
  else document.getElementById('uploadResult').textContent = j.error || 'Ошибка';
});

document.getElementById('saveBtn').addEventListener('click', async ()=>{
  const data = new FormData();
  data.append('action','save');
  data.append('title', document.querySelector('[name=title]').value);
  data.append('slug', document.querySelector('[name=slug]').value);
  data.append('date', document.querySelector('[name=date]').value);
  data.append('tag', document.querySelector('[name=tag]').value);
  data.append('excerpt', document.querySelector('[name=excerpt]').value);
  data.append('content', document.querySelector('[name=content]').value);
  const r = await fetch('/news/save.php', { method:'POST', body:data });
  const j = await r.json();
  if(j.success){ alert('Сохранено'); loadList(); }
  else alert(j.error || 'Ошибка');
});

loadList();
</script>
</body>
</html>
