<?php
/**
 * Created by PhpStorm.
 * User: Work
 * Date: 23.02.2019
 * Time: 1:35
 */

namespace Console\Crawler;

use Curl\Curl;
use DOMDocument;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class SiteCrawler
 * @package Console\Crawler
 */
class ConsoleCrawler
{
    /**
     * @var array $found_urls
     */
    private $found_urls;

    /**
     * @var array $crawled_urls
     */
    private $crawled_urls;

    /**
     * @var string $base_url
     */
    private $base_url;

    /**
     * @var string $base_host
     */
    private $base_host;

    /**
     * @var string $url
     */
    private $url;

    /**
     * @var OutputInterface $output
     */
    private $output;

    /**
     * @var int $depth
     */
    private $depth;

    public function __construct($url, $output, $depth = null)
    {
        $this->url = $url;
        $this->base_url = $this->extractBaseUrl();
        $this->found_urls = [];
        $this->crawled_urls = [];
        $this->output = $output;
        $this->depth = is_numeric($depth) ? (int)$depth : 1;
    }

    private function extractBaseUrl()
    {
        $parsed_url = parse_url($this->url);
        $scheme = isset($parsed_url['scheme']) ? $parsed_url['scheme'] . '://' : '';
        $host = isset($parsed_url['host']) ? $parsed_url['host'] : '';
        $this->base_host = $host;
        $port = isset($parsed_url['port']) ? ':' . $parsed_url['port'] : '';
        $user = isset($parsed_url['user']) ? $parsed_url['user'] : '';
        $pass = isset($parsed_url['pass']) ? ':' . $parsed_url['pass'] : '';
        $pass = ($user || $pass) ? "$pass@" : '';
        return $scheme . $user . $pass . $host . $port;
    }

    /**
     * @param $url
     * @throws \ErrorException
     */
    public function crawlSite()
    {
        $this->crawlPage($this->base_url, $this->depth);
    }

    /**
     * @param $url
     * @param $depth
     * @throws \ErrorException
     */
    private function crawlPage($url, $depth)
    {
        if (in_array($url, $this->crawled_urls) || $depth === 0) {
            return;
        }
        $dom = new DOMDocument('1.0');
        $curl = new Curl();
        $curl->setOpt(CURLOPT_FOLLOWLOCATION, TRUE);
        $crawlResult = $curl->get($url);
        if ($crawlResult && @$dom->loadHTML($crawlResult)) {
            $this->crawled_urls[] = $url;
            $anchors = $dom->getElementsByTagName('a');
            foreach ($anchors as $element) {
                $href = $element->getAttribute('href');
                if (0 !== strpos($href, 'http')) {
                    $path = '/' . ltrim($href, '/');
                    if (extension_loaded('http')) {
                        $href = http_build_url($url, array('path' => $path));
                    } else {
                        $parts = parse_url($url);
                        $href = $parts['scheme'] . '://';
                        if (isset($parts['user']) && isset($parts['pass'])) {
                            $href .= $parts['user'] . ':' . $parts['pass'] . '@';
                        }
                        $href .= $parts['host'];
                        if (isset($parts['port'])) {
                            $href .= ':' . $parts['port'];
                        }
                        $href .= $path;
                    }
                }
                $href = mb_strtolower($href);
                if ($this->isValid($href)) {
                    if (!in_array($href, $this->found_urls)) {
                        $this->found_urls[] = $href;
                        $this->output->writeln('<info>Found link: </info>' . $href);
                    }
                    $this->crawlPage($href, $depth - 1);
                }
            }
        }
    }

    private function isValid($href)
    {
        return !$this->isExternal($href) && $this->isNotImage($href) && !$this->isAnchor($href) && !$this->isMail($href);
    }

    /**
     * Является ли найденная ссылка внешней
     * @param $href
     * @return bool
     */
    private function isExternal($href)
    {
        $parsed_host = parse_url($href, PHP_URL_HOST);
        return strpos($this->base_host, $parsed_host) === false;
    }

    /**
     * Является ли найденная ссылка картинкой
     * @param $href
     * @return bool
     */
    private function isNotImage($href)
    {
        $result = true;
        foreach (['jpg', 'png', 'jpeg', 'bmp', 'ico'] as $imageType) {
            if (mb_strpos($href, $imageType) !== false) {
                $result = false;
                break;
            }
        }
        return $result;
    }

    /**
     * Является ли найденная ссылка внешней
     * @param $href
     * @return bool
     */
    private function isAnchor($href)
    {
        return mb_strpos($href, '#') !== false;
    }


    /**
     * Является ли найденная ссылка внешней
     * @param $href
     * @return bool
     */
    private function isMail($href)
    {
        return mb_strpos($href, 'mailto:') !== false;
    }

    /**
     * Возвращает базовый URL
     * @return string
     */
    public function getBaseUrl()
    {
        return $this->base_url;
    }

    /**
     * Количество найденных ссылок
     * @return int
     */
    public function getFoundLinksCount()
    {
        return count($this->found_urls);
    }

    /**
     * Найденные ссылки
     * @return array
     */
    public function getFoundLinks()
    {
        return $this->found_urls;
    }

    /**
     * @return string
     */
    public function getBaseHost()
    {
        return $this->base_host;
    }
}
