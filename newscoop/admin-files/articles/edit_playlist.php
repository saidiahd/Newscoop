<?php if (empty($userIsBlogger)) : ?>
<div class="articlebox" title="<?php putGS('Featured Article Lists'); ?>">
	<div>
		<div id="playlist" style="margin-left:8px">
    		<div id="playlistArticles" style="display:block; padding-bottom:8px;">

    		</div>

    		<label>
			<?php
			    try
			    {
    			    $playlists = Zend_Registry::get('container')->getService('em')
    			        ->getRepository('Newscoop\Entity\Playlist')
    			        ->getArticlePlaylists(Input::Get('f_article_number', 'int', 1));
                    $playlistsData = array();
                    foreach ( $playlists as $playlistArticle) {
                        $playlistsData[] = (object) array
                        (
                			"name" => $playlistArticle->getPlaylist()->getName(),
                    		"id" => $playlistArticle->getPlaylist()->getId()
                        );
                    }
			    }
			    catch(\Exception $e){ $playlistsData = array(); }
            ?>
            <ul id="added-to-playlists">
	            <?php foreach ($playlistsData as $playlist) : ?>
				<li playlist-id="<?php echo $playlist->id ?>">
		            <?php echo $this->view->escape($playlist->name); ?>
				</li>
                <?php endforeach; ?>
			</ul>
			</label>

			<?php if ($inEditMode && $GLOBALS['controller']->getHelper('acl')->isAllowed('playlist', 'manage')) : ?>
			<label>
            <span id="playlist-default-message" <?php if (count($playlistsData)) : ?>style="display:none"<?php endif; ?>>
			    <?php putGS('Add article to a list'); ?>
			</span>
			</label>

    		<a class="iframe ui-state-default icon-button right-floated"
    			popup-width="600"
        		href="<?php echo camp_html_article_url($articleObj, $f_language_id, "playlist/popup.php"); ?>">
       		<span class="ui-icon ui-icon-pencil"></span><?php putGS('Edit'); ?></a>
       		<?php endif; ?>
		</div>
	</div>
</div>
<?php endif; ?>
