<?php
/******************************************************************************
 *
 * Subrion - open source content management system
 * Copyright (C) 2018 Intelliants, LLC <https://intelliants.com>
 *
 * This file is part of Subrion.
 *
 * Subrion is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Subrion is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Subrion. If not, see <http://www.gnu.org/licenses/>.
 *
 *
 * @link https://subrion.org/
 *
 ******************************************************************************/

if (iaView::REQUEST_HTML == $iaView->getRequestType() && $iaView->blockExists('twitter_feed')) {
    require_once __DIR__ . '/classes/TwitterAPIExchange.php';

    $iaTwitter = new TwitterAPIExchange([
        'oauth_access_token' => $iaCore->get('oauth_access_token'),
        'oauth_access_token_secret' => $iaCore->get('oauth_access_token_secret'),
        'consumer_key' => $iaCore->get('twitter_feed_consumer_key'),
        'consumer_secret' => $iaCore->get('twitter_feed_consumer_secret')
    ]);

    $tweets = [];
    if ($response = $iaTwitter
        ->setGetField('?screen_name=' . $iaCore->get('twitter_feed_username') . '&count=' . $iaCore->get('twitter_feed_number_of_tweets'))
        ->buildOauth('https://api.twitter.com/1.1/statuses/user_timeline.json', 'GET')
        ->performRequest()) {
        $response = json_decode($response);
        if (!isset($response->errors)) {
            foreach ($response as $tweet) {
                $tweets[] = [
                    'created_at' => $tweet->created_at,
                    'name' => $tweet->user->name,
                    'image' => $tweet->user->profile_image_url,
                    'screen_name' => $tweet->user->screen_name,
                    'text' => preg_replace('#((http|https):\/\/[a-z0-9_\.\-\+\&\!\#\~\/\,]+)#i', '<a href="$1" target="_blank">$1</a>', $tweet->text),
                    'url' => 'https://twitter.com/' . $tweet->user->screen_name
                ];
            }
        }
    }
    $iaView->assign('timeline', $tweets);
}
