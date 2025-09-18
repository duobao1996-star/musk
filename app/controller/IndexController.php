<?php

namespace app\controller;

use support\Request;

class IndexController
{
    public function index(Request $request)
    {
        return <<<HTML
<!doctype html>
<html lang="zh-CN">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Webman API 2.0</title>
  <style>
    body { font-family: -apple-system, BlinkMacSystemFont, Segoe UI, Roboto, Helvetica, Arial, sans-serif; margin: 0; padding: 40px; color: #1f2937; background: #f8fafc; }
    .card { max-width: 720px; margin: 0 auto; background: #fff; border-radius: 12px; box-shadow: 0 8px 24px rgba(0,0,0,.08); padding: 28px; }
    h1 { margin: 0 0 12px; font-size: 28px; color: #111827; }
    p { margin: 6px 0; line-height: 1.6; }
    .link { display: inline-block; margin-top: 16px; background: #6366f1; color: #fff; padding: 10px 16px; border-radius: 8px; text-decoration: none; }
    .meta { margin-top: 18px; color: #6b7280; font-size: 14px; }
  </style>
  </head>
  <body>
    <div class="card">
      <h1>Webman API 2.0 已运行</h1>
      <p>欢迎使用。你可以直接打开本地接口文档进行联调。</p>
      <a class="link" href="/api-docs">进入接口文档 /api-docs</a>
      <div class="meta">如果你看到之前的“内容被屏蔽”提示，那是因为首页曾嵌入外部站点被 CSP 拦截。现在已改为本地页面。</div>
    </div>
  </body>
</html>
HTML;
    }

    public function view(Request $request)
    {
        return view('index/view', ['name' => 'webman']);
    }

    public function json(Request $request)
    {
        return json(['code' => 0, 'msg' => 'ok']);
    }

    public function apiDocs(Request $request)
    {
        return view('api-docs');
    }

}
