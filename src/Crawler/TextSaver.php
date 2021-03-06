<?php
/**
 * Created by PhpStorm.
 * User: Work
 * Date: 23.02.2019
 * Time: 1:36
 */

namespace Console\Crawler;

use andreskrey\Readability\Configuration;
use andreskrey\Readability\ParseException;
use andreskrey\Readability\Readability;
use Curl\Curl;


class TextSaver
{
    /**
     * @var string $url
     */
    private $url;

    /**
     * @var string $base_directory
     */
    private $base_directory;

    /**
     * @var string $errorMessage
     */
    private $errorMessage;

    /**
     * TextSaver constructor.
     * @param $url
     * @param $output
     * @throws \Exception
     */
    public function __construct($url, $output)
    {
        $this->url = $url;
        $this->base_directory = (__DIR__) . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'output';
        $this->createDirectory($this->base_directory);
    }

    /**
     * @param $path
     * @param int $mode
     * @return bool
     * @throws \Exception
     */
    private function createDirectory($path, $mode = 0775)
    {
        if (is_dir($path)) {
            return true;
        }
        try {
            if (!mkdir($path, $mode)) {
                return false;
            }
        } catch (\Exception $e) {
            if (!is_dir($path)) {
                throw new \Exception("Failed to create directory \"$path\": " . $e->getMessage(), $e->getCode(), $e);
            }
        }
        try {
            return chmod($path, $mode);
        } catch (\Exception $e) {
            throw new \Exception("Failed to change permissions for directory \"$path\": " . $e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * @param $folder
     * @return bool|int
     * @throws \Exception
     */
    public function save($folder)
    {
        $readability = new Readability(new Configuration());
        $curl = new Curl();
        $html = $curl->get($this->url);
        $result = false;
        $path = $this->base_directory . DIRECTORY_SEPARATOR . $folder;
        if ($html) {
            try {
                $readability->parse($html);
                if ($this->createDirectory($path)) {
                    $title = str_replace('/', '-', $this->getFileNameFromUrl());
                    $fileContent = strip_tags($readability->getTitle() . PHP_EOL . $readability->getContent());
                    $fileName = $path . DIRECTORY_SEPARATOR . $title . '.txt';
                    $result = file_put_contents($fileName, $fileContent);
                    if (($result === false) || ($result == -1)) {
                        $this->errorMessage = 'Couldn\'t save text to file ' . $fileName;
                    }
                }
            } catch (ParseException $e) {
                $this->errorMessage = 'Error processing text: ' . $e->getMessage();
            }
        } else {
            $this->errorMessage = 'Error getting page text: '. $curl->errorCode.' - '. $curl->errorMessage;
        }
        return $result;
    }

    /**
     * Имя файла генерируем из ссылки
     * @return string
     */
    private function getFileNameFromUrl()
    {
        $parsed_url = parse_url($this->url);
        $host = isset($parsed_url['host']) ? $parsed_url['host'] : '';
        $port = isset($parsed_url['port']) ? ':' . $parsed_url['port'] : '';
        $user = isset($parsed_url['user']) ? $parsed_url['user'] : '';
        $path = isset($parsed_url['path']) ? $parsed_url['path'] : '';
        return $user . '-' . $host . '-' . $port . '-' . $path;
    }

    public function getErrorMessage()
    {
        return $this->errorMessage;
    }
}