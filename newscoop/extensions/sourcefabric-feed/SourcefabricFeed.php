<?php
/**
 * @title Sourcefabric.org feed reader
 */
class SourcefabricFeed extends FeedWidget
{
    protected $title = 'Sourcefabric.org feed reader';

    protected $url = 'http://feeds.feedburner.com/SourcefabricNews?format=xml';

    /**
     * @setting
     * @label Number
     */
    protected $count = 5;
}
