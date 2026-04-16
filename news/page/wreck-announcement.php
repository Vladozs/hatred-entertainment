<!doctype html>
<html lang="ru">
<head>
  <meta charset="utf-8"/>
  <meta name="viewport" content="width=device-width,initial-scale=1"/>
  <title>WRECK Eternal планируется к выходу в феврале 2026 года.</title>
  <meta name="description" content="1 акт игры будет готов к февралю.">
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
      <h1>WRECK Eternal планируется к выходу в феврале 2026 года.</h1>
      <div class="meta">13.09.2025 23:29 • <strong>Анонс</strong></div><div class="news-photo" style="text-align:center;">
        <img src="/news/uploads/066b22a64000e23e.png" alt="" style="max-width:30%; height:auto; border-radius:16px;">
      </div>
      <div class="content"><div class="text-block">
Сюжет игры не раскрывается, и не будет раскрыт до выхода. Игра будет поделена на 5 актов. Выход планируется в Steam, VK Play, itch io. Возможен выход на другие площадки. 
<br>
<br>
WRECK Eternal - это увлекательный бумер-шутер с возможностью парировать снаряды, останавливать время и раскручивать револьвер на пальце со скоростью света!</div></div>

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
    const related = j.articles.filter(a=>a.slug!=="wreck-announcement")
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
</html>