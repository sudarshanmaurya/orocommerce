<?php

namespace OroB2B\Bundle\FrontendBundle\Test;

use Symfony\Component\DomCrawler\Crawler;

use Oro\Bundle\TestFrameworkBundle\Test\Client as BaseClient;

class Client extends BaseClient
{
    /**
     * {@inheritdoc}
     */
    public function request(
        $method,
        $uri,
        array $parameters = [],
        array $files = [],
        array $server = [],
        $content = null,
        $changeHistory = true
    ) {
        $crawler = parent::request($method, $uri, $parameters, $files, $server, $content, $changeHistory);

        $this->checkForBackendUrls($uri, $crawler);

        return $crawler;
    }

    /**
     * @param $uri string
     * @param $crawler
     */
    protected function checkForBackendUrls($uri, Crawler $crawler)
    {
        if ($this->isFrontendUri($uri)) {
            $backendPrefix = $this->getBackendPrefix();
            if (strpos($crawler->html(), $backendPrefix) !== false) {
                throw new \RuntimeException(
                    sprintf('Response to uri "%s" contains link to backend (with "%s" prefix).', $uri, $backendPrefix)
                );
            }
        }
    }

    /**
     * @param string $uri
     * @return bool
     */
    protected function isFrontendUri($uri)
    {
        return strpos($uri, $this->getBackendPrefix()) === false;
    }

    /**
     * @return string
     */
    protected function getBackendPrefix()
    {
        return $this->getContainer()->getParameter('web_backend_prefix');
    }
}
