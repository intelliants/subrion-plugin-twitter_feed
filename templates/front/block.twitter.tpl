{if isset($timeline) && !empty($timeline[0].text)}
    <div class="ia-items latest-tweets">
        {foreach $timeline as $entry}
            <div class="media ia-item ia-item-bordered-bottom">
                <a class="pull-left" href="{$entry.url}" target="_blank">
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
    <p>{lang key='no_tweets_yet'}</p>
{/if}