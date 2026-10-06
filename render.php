<?php
$config = json_decode(file_get_contents(__DIR__.'/applications.json'), true, 512, JSON_THROW_ON_ERROR);
$template = file_get_contents(__DIR__.'/index.html');
$cloned = 0;
$html = preg_replace_callback('/<article\b[^>]*data-name="([^"]+)"[^>]*>.*?<\/article>/s', function ($match) use ($config, &$cloned) {
    $application = $config['applications'][$match[1]] ?? null;
    $installed = $application && (is_dir($application['path'].'/.git') || is_file($application['path'].'/.git'));
    $url = $installed ? ($application['url'] ?? null) : null;
    $card = $match[0];
    if ($installed) {
        $cloned++;
        $status = $url ? 'Disponibil · Clonat' : 'Clonat · Publicarea nu este configurată';
    } else {
        $card = str_replace('class="card"', 'class="card disabled" aria-disabled="true"', $card);
        $status = 'Neclonat · Indisponibil';
    }
    $card = preg_replace_callback('/<a\b([^>]*)>(.*?)<\/a>/s', function ($link) use ($installed, $url) {
        $local = preg_match('/href="http:\/\/(?:127\.0\.0\.1|[^"\/]*localhost)[^"]*"/', $link[1]);
        if (!$installed || ($local && !$url)) {
            $attributes = preg_replace('/\s*(?:href|target|rel|aria-label)="[^"]*"/', '', $link[1]);
            return '<span'.$attributes.'>'.$link[2].'</span>';
        }
        if ($local) {
            $attributes = preg_replace('/href="[^"]*"/', 'href="'.htmlspecialchars($url, ENT_QUOTES, 'UTF-8').'"', $link[1]);
            return '<a'.$attributes.'>'.str_replace('Local', 'Deschide', $link[2]).'</a>';
        }
        return $link[0];
    }, $card);
    if ($installed && !$url) {
        $repo = htmlspecialchars($application['repository'], ENT_QUOTES, 'UTF-8');
        $card = preg_replace('/<\/article>$/', '<a class="resource-link project-open" href="'.$repo.'" target="_blank" rel="noreferrer">GitHub</a></article>', $card);
    }
    return str_replace('</article>', '<p class="availability">'.$status.'</p></article>', $card);
}, $template);
$html = preg_replace('/(<span id="count">)\d+(<\/span>)/', '${1}12$2', $html);
$html = str_replace('proiecte · Rating', 'proiecte · '.$cloned.' clonate · Rating', $html);
header('Content-Type: text/html; charset=UTF-8');
echo $html;
