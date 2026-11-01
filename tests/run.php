<?php
require dirname(__DIR__).'/app/bootstrap.php';
$tests=[
  'YouTube URL accepted'=>validate_media_url('https://www.youtube.com/watch?v=abc123'),
  'Short YouTube URL accepted'=>validate_media_url('https://youtu.be/abc123'),
  'TikTok URL accepted'=>validate_media_url('https://www.tiktok.com/@creator/video/123'),
  'Instagram URL accepted'=>validate_media_url('https://www.instagram.com/reel/abc/'),
  'Unsupported host rejected'=>!validate_media_url('https://example.com/video'),
  'JavaScript URL rejected'=>!validate_media_url('javascript:alert(1)'),
  'YouTube platform mapped'=>platform_from_url('https://www.youtube.com/watch?v=x')==='youtube',
  'Podcast platform mapped'=>platform_from_url('https://open.spotify.com/episode/x')==='podcast',
  'CSRF token generated'=>strlen(csrf_token())===64,
];
$failed=0;foreach($tests as $name=>$ok){echo ($ok?'[PASS] ':'[FAIL] ').$name.PHP_EOL;if(!$ok)$failed++;}
echo count($tests).' checks, '.$failed.' failures'.PHP_EOL;exit($failed?1:0);
