<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006~2016 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: liu21st <liu21st@gmail.com>
// +----------------------------------------------------------------------
namespace behavior;

use think\Config;

/**
 * 系统行为扩展：表单令牌生成
 */
class TokenBuild
{

    protected $config = [
        'token_on'    => false,
        'token_name'  => '__hash__',
        'token_type'  => 'md5',
        'token_reset' => true,
    ];

    public function __construct()
    {
        if (Config::has('token')) {
            $this->config = array_merge($this->config, Config::get('token'));
        }
    }

    public function run(&$content)
    {
        if ($this->config['token_on']) {
            list($tokenName, $tokenKey, $tokenValue) = $this->getToken();
            $input_token                             = '<input type="hidden" name="' . $tokenName . '" value="' . $tokenKey . '_' . $tokenValue . '" />';
            $meta_token                              = '<meta name="' . $tokenName . '" content="' . $tokenKey . '_' . $tokenValue . '" />';
            if (strpos($content, '{__TOKEN__}')) {
                // 指定表单令牌隐藏域位置
                $content = str_replace('{__TOKEN__}', $input_token, $content);
            } elseif (preg_match('/<\/form(\s*)>/is', $content, $match)) {
                // 智能生成表单令牌隐藏域
                $content = str_replace($match[0], $input_token . $match[0], $content);
            }
            $content = str_ireplace('</head>', $meta_token . '</head>', $content);
        } else {
            $content = str_replace('{__TOKEN__}', '', $content);
        }
    }

    //获得token
    private function getToken()
    {
        $tokenName = $this->config['token_name'];
        $tokenType = $this->config['token_type'];
        if (!isset($_SESSION[$tokenName])) {
            $_SESSION[$tokenName] = [];
        }
        // 标识当前页面唯一性
        $tokenKey = md5($_SERVER['REQUEST_URI']);
        if (isset($_SESSION[$tokenName][$tokenKey])) {
            // 相同页面不重复生成session
            $tokenValue = $_SESSION[$tokenName][$tokenKey];
        } else {
            $tokenValue                      = is_callable($tokenType) ? $tokenType(microtime(true)) : md5(microtime(true));
            $_SESSION[$tokenName][$tokenKey] = $tokenValue;
            if (IS_AJAX && $this->config['token_reset']) {
                header($tokenName . ': ' . $tokenKey . '_' . $tokenValue);
            }
            //ajax需要获得这个header并替换页面中meta中的token值
        }
        return [$tokenName, $tokenKey, $tokenValue];
    }
}
