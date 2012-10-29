<?php
/*
Plugin Name: Excerpt Plugin for Nihongo
Plugin URI: https://github.com/techscore/excerpt-plugin-for-nihongo
Description: 抜粋を良い感じに表示する、日本語向けのプラグイン。以下の優先順位で表示します。1. 抜粋があれば、抜粋を表示。2. moreタグが設定されていたら、そこまでを表示。3. 規定文字数まで句点を探し、近い位置まで表示。4. 句点がないか、規定文字数よりも大きすぎたら、規定文字数まで区切って表示。<strong>設定はプラグインを直接編集してください。</strong>
Version: 0.0.2
Author: Kentaro Kawano
Author URI: 
License: 3-clause BSD license
*/

/* --- LICENSE ---
Copyright (c) 2012, Synergy Marketing, Inc.
All rights reserved.

Redistribution and use in source and binary forms, with or without
modification, are permitted provided that the following conditions
are met:

Redistributions of source code must retain the above copyright notice,
this list of conditions and the following disclaimer.

Redistributions in binary form must reproduce the above copyright
notice, this list of conditions and the following disclaimer in the
documentation and/or other materials provided with the distribution.

Neither the name of the Synergy Marketing, Inc. nor the names of its
contributors may be used to endorse or promote products derived from
this software without specific prior written permission.

THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS
"AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT
LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS
FOR A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE
COPYRIGHT HOLDER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT,
INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING,
BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS
OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED
AND ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY,
OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF
THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH
DAMAGE.
 */

namespace EP4N;

/* ============================== ここから設定 ============================== */

# 取得する文字列の基準の長さ
define('CONTENT_LENGTH', 100);

# 本文へのリンクのアンカーテキスト
define('READMORE_ANCHOR_TEXT', '続きを読む...');

# 本文へのリンクの前に付く文字列
define('READMORE_PREFIX', '<br>');

# 本文へのリンクの後に付く文字列
define('READMORE_SUFFIX', '');

# 区切り文字（デフォルトは句点）
define('DELIMITER', '。');

/* ============================== 設定はここまで ============================== */

function append_readmore($output) {
    return implode('', array(
            $output,
            READMORE_PREFIX,
            '<a href="', get_permalink(), '">',
            READMORE_ANCHOR_TEXT,
            '</a>',
            READMORE_SUFFIX
    ));
}

function get_the_excerpt($output) {
    global $post;

    // パスワード付きの場合は、その旨を表示する
    if (post_password_required($post)) {
        $output = __('There is no excerpt because this is a protected post.');
        return $output;
    }

    // 抜粋を取得
    $output = $post->post_excerpt;

    // 抜粋がある場合はそれを表示する
    if (mb_strlen($output)) {
        return append_readmore($output);
    }

    // more がある場合は、そこまでを表示する
    $match = preg_split('/<!--more(.*?)?-->/', $post->post_content);
    if ($match && count($match) > 1) {
        return append_readmore($match[0]);
    }

    // タグを除去した状態のテキストを取得する
    $content = strip_tags($post->post_content);

    $pos = mb_strpos($content, DELIMITER, 0);

    // 区切り文字が全く見つからなかった場合か、
    // 最初の区切り文字の位置が規定文字数を大幅に超える場合、
    // 規定文字数で表示する
    if ($pos === false || $pos > CONTENT_LENGTH * 1.1) {
        return append_readmore(mb_substr($content, 0, CONTENT_LENGTH));
    }

    // 規定文字数に近づける
    $prev_pos = $pos;
    while ($pos !== false) {
        // 次の区切り文字の位置を取得
        $pos = mb_strpos($content, DELIMITER, $prev_pos + 1);

        // 次の区切り文字が見つからない場合、前回の位置を採用する
        if ($pos === false) {
            $pos = $prev_pos;
            break;
        }

        // 規定文字数を超えた場合、前回の結果と比べて規定文字数に近い方を採用する
        if ($pos > CONTENT_LENGTH) {
            $d1 = abs(CONTENT_LENGTH - $prev_pos);
            $d2 = abs(CONTENT_LENGTH - $pos);
            if ($d1 < $d2) {
                $pos = $prev_pos;
            }
            break;
        }
        $prev_pos = $pos;
    }

    // 区切り文字を含めた位置まで取得し、本文へのリンクを作成する
    return append_readmore(mb_substr($content, 0, $pos + mb_strlen(DELIMITER)));

}

add_filter('get_the_excerpt', __NAMESPACE__ . '\get_the_excerpt');
