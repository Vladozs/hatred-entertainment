<?php
// news/save.php
session_start();
header('Content-Type: application/json');

if (empty($_SESSION['admin_logged_in'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

$base = __DIR__;
$jsonFile = $base . '/news.json';
$articles = [];

if (file_exists($jsonFile)) {
    $raw = file_get_contents($jsonFile);
    $data = json_decode($raw, true);
    $articles = $data['articles'] ?? [];
}

$action = $_POST['action'] ?? '';

if ($action === 'delete') {
    $slug = $_POST['slug'] ?? '';
    $articles = array_values(array_filter($articles, function ($a) use ($slug) {
        return ($a['slug'] ?? '') !== $slug;
    }));
    file_put_contents($jsonFile, json_encode(['articles' => $articles], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    $static = $base . '/page/' . $slug . '.php';
    if (file_exists($static)) @unlink($static);
    echo json_encode(['success' => true, 'message' => 'Удалено']);
    exit;
}

if ($action === 'save') {
    $title = trim($_POST['title'] ?? '');
    $slug = trim($_POST['slug'] ?? '');
    $date = trim($_POST['date'] ?? date('c'));
    $tag = trim($_POST['tag'] ?? 'Прочее');
    $excerpt = trim($_POST['excerpt'] ?? '');
    $content = $_POST['content'] ?? '';

    if (!$title || !$slug) {
        echo json_encode(['success' => false, 'error' => 'Недостаточно данных']);
        exit;
    }

    if (!preg_match('/^[a-z0-9\-]+$/', $slug)) {
        echo json_encode(['success' => false, 'error' => 'Слаг должен содержать только латиницу, цифры и дефисы']);
        exit;
    }

    // ---- Парсим preview и scale ----
    $preview = null;
    $previewScale = 1;
    if (preg_match('/<!--\s*preview:(.*?)\s*(?:scale:([\d\.]+))?\s*-->/', $content, $m)) {
        $preview = trim($m[1]);
        if (!empty($m[2])) {
            $previewScale = floatval($m[2]);
        }
    } else {
        if (!empty($articles) && isset($foundIndex) && $foundIndex !== null) {
            $preview = $articles[$foundIndex]['preview'] ?? null;
        }
    }
    
    if ($preview) {
        $previewComment = '<!-- preview:' . $preview;
        if ($previewScale != 1) $previewComment .= ' scale:' . $previewScale;
        $previewComment .= ' -->';
        $content = preg_replace('/<!--\s*preview:.*?-->/', '', $content);
        $content = $previewComment . "\n" . ltrim($content);
    }


    // Найти новость по slug
    $foundIndex = null;
    foreach ($articles as $i => $a) {
        if (($a['slug'] ?? '') === $slug) {
            $foundIndex = $i;
            break;
        }
    }

    $newItem = [
        'id' => $foundIndex !== null ? $articles[$foundIndex]['id'] : uniqid(),
        'slug' => $slug,
        'title' => $title,
        'date' => $date,
        'tag' => $tag,
        'preview' => $preview ?: ($foundIndex !== null ? ($articles[$foundIndex]['preview'] ?? '') : ''),
        'excerpt' => $excerpt,
        'content' => $content
    ];

    if ($foundIndex !== null) {
        $articles[$foundIndex] = $newItem;
    } else {
        $articles[] = $newItem;
    }

    // Сохранить news.json
    file_put_contents($jsonFile, json_encode(['articles' => $articles], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

    // --- Генерация статической PHP-страницы ---
    $blocks = preg_split('/<!--block-->/', $content);
    $rendered = '';
    foreach ($blocks as $b) {
        $b = trim($b);
        if ($b === '') continue;

        // Вариант 1: img:/path scale=...
        if (str_starts_with($b, 'img:')) {
            $url = trim(substr($b, 4));
            $scale = 1;
            if (preg_match('/\s+scale=([\d\.]+)/', $url, $m)) {
                $scale = floatval($m[1]);
                $url = preg_replace('/\s+scale=[\d\.]+/', '', $url);
            }
            $rendered .= '<div class="news-photo" style="text-align:center;">
                <img src="' . htmlspecialchars($url) . '" alt="" style="max-width:' . (100*$scale) . '%; height:auto; border-radius:16px;">
            </div>';
        } else {
            // Разрешённые теги
            $allowed = '<p><br><strong><em><ul><ol><li><img>';
            $safe = strip_tags($b, $allowed);

            // Вариант 2: обычные <img ... scale="x"> или <img ... data-scale="x">
            $safe = preg_replace_callback('/<img([^>]+)>/i', function($m) {
                $tag = $m[1];
                $scale = 1;
                if (preg_match('/(?:scale|data-scale)=["\']?([\d\.]+)/i', $tag, $sm)) {
                    $scale = floatval($sm[1]);
                    // удаляем scale/data-scale из тега
                    $tag = preg_replace('/\s*(?:scale|data-scale)=["\']?[\d\.]+/i', '', $tag);
                }
                return '<div class="news-photo" style="text-align:center;">
                    <img ' . $tag . ' style="max-width:' . (100*$scale) . '%; height:auto; border-radius:16px;">
                </div>';
            }, $safe);

            $rendered .= '<div class="text-block">' . $safe . '</div>';
        }
    }

    $pageDir = $base . '/page';
    if (!is_dir($pageDir)) {
        mkdir($pageDir, 0755, true);
    }
    $staticPath = $pageDir . '/' . $slug . '.php';

    $articleHtml = '<!doctype html>
<html lang="ru">
<head>
  <meta charset="utf-8"/>
  <meta name="viewport" content="width=device-width,initial-scale=1"/>
  <title>' . htmlspecialchars($title) . '</title>
  <meta name="description" content="' . htmlspecialchars($excerpt) . '">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@400;600&family=Inter:wght@400;600&display=swap" rel="stylesheet">
  <style>
    :root { --bg:#000; --text:#fff; --card:#111; --accent:#c50000; }
    body.light { --bg:#fff; --text:#000; --card:#f2f2f2; --accent:#c50000; }
    body { background:var(--bg); color:var(--text); margin:0; font-family:Inter,sans-serif;
           transition:background .3s,color .3s; }
    .navbar { display:flex; justify-content:space-between; align-items:center;
              padding:12px 20px; background:var(--card); border-bottom:1px solid rgba(255,255,255,0.1);
              flex-wrap:wrap; }
    .navbar .brand { display:flex; align-items:center; gap:10px; text-decoration:none; }
    .navbar .brand img { width:28px; height:28px; }
    .navbar .brand span { font-family:Orbitron, monospace; font-size:1.2rem; color:var(--accent); }
    .navbar .controls { display:flex; gap:10px; align-items:center; margin-top:8px; }
    @media(min-width:600px){ .navbar .controls { margin-top:0; } }
    .btn-theme { cursor:pointer; padding:6px 12px; border-radius:8px;
                 background:var(--accent); color:#fff; border:none; }
    .container { max-width:1200px; margin:0 auto; padding:20px; display:flex; flex-wrap:wrap; gap:20px; }
    .main { flex:3 1 600px; }
    .sidebar { flex:1 1 280px; background:var(--card); border-radius:12px; min-height:200px;
               display:flex; justify-content:center; align-items:center; color:#777; }
    h1 { font-family:Orbitron, monospace; font-size:2rem; margin-bottom:0.5rem; }
    .meta { color:#888; margin-bottom:1.5rem; font-size:0.95rem; }
    .meta strong { color:var(--accent); }
    .news-photo { margin:20px 0; }
    .text-block { margin-bottom:1.2rem; line-height:1.6; font-size:1.05rem; color:var(--text); }
    .text-block img { max-width:100%; height:auto; display:block; margin:20px auto; border-radius:16px; }
    .related { margin-top:40px; }
    .related h2 { font-family:Orbitron, monospace; margin-bottom:1rem; }
    #related { display:grid; grid-template-columns:repeat(auto-fit,minmax(250px,1fr)); gap:20px; }
    #related a { text-decoration:none; color:inherit; }
    #related .card { background:var(--card); border-radius:12px; overflow:hidden; }
    #related .card img { width:100%; height:150px; object-fit:cover; }
    #related .card .info { padding:10px; }
    #related .card h3 { font-family:Orbitron, monospace; font-size:1.1rem; margin:6px 0; color:var(--text); }
  </style>
</head>
<body>
  <div class="navbar">
    <a href="/" class="brand">
      <img src="https://hatred-entertainment.ru/favicon.ico" alt="logo">
      <span>HATRED Entertainment</span>
    </a>
    <div class="controls">
      <button class="btn-theme" id="themeToggle">Тема</button>
    </div>
  </div>

  <div class="container">
    <div class="main">
      <a href="/news/index.html" style="color:var(--accent);text-decoration:none;display:inline-block;margin-bottom:20px;">← Все новости</a>
      <h1>' . htmlspecialchars($title) . '</h1>
      <div class="meta">' . htmlspecialchars(date('d.m.Y H:i', strtotime($date))) . ' • <strong>' . htmlspecialchars($tag) . '</strong></div>' .
      ($preview ? '<div class="news-photo" style="text-align:center;">
        <img src="' . htmlspecialchars($preview) . '" alt="" style="max-width:' . (100*$previewScale) . '%; height:auto; border-radius:16px;">
      </div>' : '') . '
      <div class="content">' . $rendered . '</div>

      <div class="related">
        <h2>Похожие</h2>
        <div id="related"></div>
      </div>
    </div>
  </div>

  <script>
  // Тема
  function applyTheme(t){ document.body.classList.remove("light"); if(t==="light") document.body.classList.add("light"); }
  applyTheme(localStorage.getItem("theme")||"dark");
  document.getElementById("themeToggle").onclick=()=>{
    const newTheme=document.body.classList.contains("light")?"dark":"light";
    applyTheme(newTheme); localStorage.setItem("theme",newTheme);
  };

  // Похожие
  fetch("/news/news.json?nocache="+Date.now()).then(r=>r.json()).then(j=>{
    if(!j.articles) return;
    const related = j.articles.filter(a=>a.slug!=="' . $slug . '")
      .sort((a,b)=> new Date(b.date)-new Date(a.date))
      .slice(0,3);
    const box=document.getElementById("related");
    related.forEach(article=>{
      const a=document.createElement("a");
      a.href="/news/page/"+article.slug;
      a.innerHTML=`<div class="card">
        <img src="${article.preview||"/image/hat-red.png"}" alt="">
        <div class="info">
          <div style="font-size:0.85rem;color:#888;">${new Date(article.date).toLocaleDateString("ru-RU")} • <strong style="color:var(--accent)">${article.tag}</strong></div>
          <h3>${article.title}</h3>
        </div>
      </div>`;
      box.appendChild(a);
    });
  });
  </script>
</body>
</html>';

    file_put_contents($staticPath, $articleHtml);

    echo json_encode(['success' => true, 'message' => 'Сохранено']);
    exit;
}

echo json_encode(['success' => false, 'error' => 'Unknown action']);
exit;
