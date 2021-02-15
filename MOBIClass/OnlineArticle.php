<?php

/**
 * Description of OnlineArticle.
 *
 * @author Sander
 */
class OnlineArticle extends ContentProvider
{
    private $text;
    private $images;
    private $metadata = array();
    private $imgCounter = 0;

    public function __construct($url, $html)
    {

        $doc = new DOMDocument();
        set_error_handler( '__return_null' );
        $doc->loadHTML( $html );
        restore_error_handler();
        $doc->normalizeDocument();

        $this->images = $this->handleImages($doc, $url);
        $this->text = $doc->saveHTML();
    }

    /**
     * Get the text data to be integrated in the MOBI file.
     *
     * @return string
     */
    public function getTextData()
    {
        return $this->text;
    }
    /**
     * Get the images (an array containing the jpeg data). Array entry 0 will
     * correspond to image record 0.
     *
     * @return array
     */
    public function getImages()
    {
        return $this->images;
    }
    /**
     * Get the metadata in the form of a hashtable (for example, title or author).
     *
     * @return array
     */
    public function getMetaData()
    {
        return $this->metadata;
    }


    public function setMetadata($key, $value)
    {
        $this->metadata[$key] = $value;
    }

    /**
     * @param DOMElement $dom
     *
     * @return array
     */
    private function handleImages($dom, $url)
    {
        $images = array();

        $parts = parse_url($url);

        $savedImages = array();

        $imgElements = $dom->getElementsByTagName('img');
        foreach ($imgElements as $img) {
            $src = $img->getAttribute('src');

            $is_root = false;
            if (substr($src, 0, 1) == '/') {
                $is_root = true;
            }

            $parsed = parse_url($src);

            if (!isset($parsed['host'])) {
                if ($is_root) {
                    $src = http_build_url($url, $parsed, HTTP_URL_REPLACE);
                } else {
                    $src = http_build_url($url, $parsed, HTTP_URL_JOIN_PATH);
                }
            }
            $img->setAttribute('src', '');
            if (isset($savedImages[$src])) {
                $img->setAttribute('recindex', $savedImages[$src]);
            } else {
                $image = ImageHandler::DownloadImage($src);

                if ($image !== false) {
                    $images[$this->imgCounter] = new FileRecord(new Record($image));

                    $img->setAttribute('recindex', $this->imgCounter);
                    $savedImages[$src] = $this->imgCounter;
                    ++$this->imgCounter;
                }
            }
        }

        return $images;
    }
}
