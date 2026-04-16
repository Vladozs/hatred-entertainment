<?php
header('Content-Type: application/rss+xml; charset=utf-8');

$base = __DIR__;
$jsonFile = $base . '/news.json';
$articles = [];

if (file_exists($jsonFile)) {
    $data = json_decode(file_get_contents($jsonFile), true);
    $articles = $data['articles'] ?? [];
}

echo '<?xml version="1.0" encoding="UTF-8"?>';
?>
<rss version="2.0">
<channel>
  <title>HATRED Entertainment - Новости</title>
  <link>https://hatred-entertainment.ru/news/</link>
  <description>Последние обновления, релизы и анонсы.</description>
  <language>ru-ru</language>
  <lastBuildDate><?= date(DATE_RSS) ?></lastBuildDate>

<?php foreach ($articles as $a): ?>
  <item>
    <title><?= htmlspecialchars($a['title']) ?></title>
    <link>https://hatred-entertainment.ru/news/page/<?= htmlspecialchars($a['slug']) ?></link>
    <guid>https://hatred-entertainment.ru/news/page/<?= htmlspecialchars($a['slug']) ?></guid>
    <pubDate><?= date(DATE_RSS, strtotime($a['date'])) ?></pubDate>
    <category><?= htmlspecialchars($a['tag']) ?></category>
    <description><![CDATA[
      <?= $a['excerpt'] ?: mb_substr(strip_tags($a['content']), 0, 200).'...' ?>
    ]]></description>
  </item>
<?php endforeach; ?>

</channel>
</rss>
