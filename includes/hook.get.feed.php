<?php
//##copyright##

if (iaView::REQUEST_HTML == $iaView->getRequestType() && $iaView->blockExists('twitter_feed'))
{
	require_once dirname(__FILE__) . '/classes/TwitterAPIExchange.php';

	$iaTwitter = new TwitterAPIExchange(array(
		'oauth_access_token' => $iaCore->get('oauth_access_token'),
		'oauth_access_token_secret' => $iaCore->get('oauth_access_token_secret'),
		'consumer_key' => $iaCore->get('twitter_feed_consumer_key'),
		'consumer_secret' => $iaCore->get('twitter_feed_consumer_secret')
	));

	$tweets = array();
	if ($response = $iaTwitter
		->setGetField('?screen_name=' . $iaCore->get('twitter_feed_username') . '&count=' . $iaCore->get('twitter_feed_number_of_tweets'))
		->buildOauth('https://api.twitter.com/1.1/statuses/user_timeline.json', 'GET')
		->performRequest())
	{
		$response = json_decode($response);
		if (!isset($response->errors))
		{
			foreach ($response as $tweet)
			{
				$tweets[] = array(
					'created_at' => $tweet->created_at,
					'name' => $tweet->user->name,
					'image' => $tweet->user->profile_image_url,
					'screen_name' => $tweet->user->screen_name,
					'text' => preg_replace('#((http|https):\/\/[a-z0-9_\.\-\+\&\!\#\~\/\,]+)#i', '<a href="$1" target="_blank">$1</a>', $tweet->text),
					'url' => 'http://twitter.com/' . $tweet->user->screen_name
				);
			}
		}
	}
	$iaView->assign('timeline', $tweets);
}