# PukiWiki Shiki Syntax Highlighter プラグイン

PukiWiki 上で **Shiki (https://shiki.style/)** を使ったモダンなシンタックスハイライトを行うためのプラグインです。
CDN 経由で Shiki を読み込み、**PHP 側は最小実装・クライアント側でハイライト**する構成になっています。

## 特長

- Shiki v3 を使用した高品質なシンタックスハイライト
- CDN 読み込み（ビルド不要）
- 言語・テーマ指定対応
- 行番号表示（開始行指定可）
- コードブロックのタイトル表示
- 追加 CSS クラス指定可能
- プラグインのファイル1つで完結

## 動作環境

- PukiWiki 1.5.4 以降（想定）
- PHP 8.x 推奨
- JavaScript が有効なブラウザ

## インストール

1. Release から最新の `shiki.inc.php` を取得
2. PukiWiki の `plugin/` ディレクトリに配置

```text
pukiwiki/
 └─ plugin/
     └─ shiki.inc.php
```

3. PukiWiki を表示し、以下の記法が使えれば導入完了

## 使い方

### 基本

```text
#shiki(lang=js){{
console.log('Hello Shiki');
}}
```

### 引数の指定方法

`#shiki(...)` の丸括弧内に **カンマ区切り**で指定します。

```text
#shiki(lang=php, theme=github-dark){{
<?php echo 'Hello'; ?>
}}
```

### 引数一覧

| 引数名           | 説明                 | 例                   |
| ------------- | ------------------ | ------------------- |
| `lang`        | 言語指定（Shiki 対応言語）   | `lang=js`           |
| `theme`       | テーマ名               | `theme=github-dark` |
| `title`       | コードブロック上部に表示するタイトル | `title=sample.js`   |
| `linenumbers` | 行番号を表示（開始行番号）      | `linenumbers`, `linenumbers=12`     |
| `start`       | `linenumbers` の別名  | `start=10`          |
| `class`       | 追加 CSS クラス         | `class=my-code my-code-2`     |
| `diff`        | 差分（行頭に + or -）のハイライト      | `diff`    |


※ `key=value` 形式でない引数は **言語指定**として扱われます。

```text
#shiki(js){{
console.log('lang only');
}}
```

### 行番号付き

```text
#shiki(lang=ts, linenumbers=1){{
const a = 1;
const b = 2;
}}
```

### タイトル付き

```text
#shiki(lang=json, title=config.json){{
{
  "debug": true
}
}}
```

## テーマについて

デフォルトテーマは`github-dark`以下です。

Shiki が対応しているテーマ名であれば `theme=` で自由に指定できます。

## 技術的メモ

- Shiki は CDN (`https://esm.run/shiki@3`) から ESM として読み込み
- `import { createHighlighter } from 'shiki'` を利用し、ブラウザ上でハイライトを実行
- PHP 側では以下のみを担当
  - コード内容のエスケープ
  - 引数（言語・テーマ・行番号など）の解析
  - 必要な HTML 構造と data 属性の出力
- 実際のシンタックスハイライト処理は **すべてクライアント側 JavaScript** で実行
- ページ内に複数 `#shiki` があっても JS/CSS は1回のみロード
- 初期表示時は `visibility:hidden` → ハイライト完了後に表示

## 実装の詳細

### 処理フロー概要

1. PukiWiki が `#shiki(...)` を解釈し、`shiki.inc.php` が実行される
2. PHP 側で以下を生成
   - `<pre><code>` を含むプレースホルダー HTML
   - 言語・テーマ・行番号などを `data-*` 属性として付与
3. JavaScript で `<head>` にプラグイン用の `<style>` 要素を挿入
4. ページ読み込み後、 `createHighlighter` で `highlighterInstance` を一度のみ作成
   - この時点では初期テーマのみをロードする
   - この際言語はロードせず、各コードブロックの処理時に必要な言語やテーマをロードする
    ```javascript
    const highlighterInstance = await createHighlighter({
        themes: ['github-dark'],
        langs: [],
    });
    ```
5. 各コードブロックごとに `highlighterInstance.codeToHtml()` を実行し、 ハイライト済み HTML に差し替え
   - `data` 属性から言語とテーマ、タイトルや行番号指定などを取得
     - 言語やテーマがまだロードされていない場合は、ここでロードする
   - Transformer を使ってコードブロックの上にタイトル、言語ラベル、コピーボタンなどのヘッダー要素を追加
   ```javascript
   const html = await highlighterInstance.codeToHtml(text, {
        lang: lang,
        theme: theme,
        transformers: [
            insertHeaderTransformer(lang, title)
        ],
    });
    ```
6. 処理完了後、各コードブロックを表示する

## ライセンス

GPL v3 or later

## 作者

- author: kanateko
- site: [https://jpngamerswiki.com/](https://jpngamerswiki.com/)

---

不具合報告・改善案があればお気軽にどうぞ 🙌
