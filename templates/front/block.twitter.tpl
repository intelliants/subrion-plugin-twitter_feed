{if isset($timeline) && !empty($timeline[0].text)}
	<div class="ia-items latest-tweets">
		{foreach $timeline as $entry}
			<div class="media ia-item ia-item-bordered-bottom">
				<a class="pull-left" href="http://twitter.com/{$entry.screen_name}">
					<img src="{$entry.image}" weight="50">
				</a>
				<div class="media-body">
					<h5 class="media-heading"><a href="{$entry.url}" target="_blank">{$entry.name}</a></h5>
					<p>{$entry.text|strip_tags|truncate:140}</p>
					<h5>{$entry.created_at|date_format:$core.config.date_format}</h5>
				</div>
			</div>
		{/foreach}
	</div>
{else}
	{lang key='no_tweets_yet'}
{/if}