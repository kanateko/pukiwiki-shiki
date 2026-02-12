<?php
/**
 * Shiki Syntax Highlighter を使用してコードを表示するプラグイン
 *
 * @version 1.0.1
 * @author kanateko
 * @link https://jpngamerswiki.com/?f51cd63681
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPLv3 or later
*/

/**
 * ブロック型の呼び出し
 *
 * @param string ...$args 引数の配列
 * @return string 変換後のHTML
 */
function plugin_shiki_convert(string ...$args): string
{
    $shiki = new Shiki($args);
    return $shiki->convert();
}

/**
 * Shiki クラス
 * コードをハイライト表示するためのクラス
 */
class Shiki
{
    const CDN = 'https://esm.run/shiki@3';
    const DEFAULT_LANG = 'text';
    const DEFAULT_THEME = 'github-dark';
    const DEFAULT_LINENUMBERS = '';
    const DEFAULT_TITLE = '';
    const DEFAULT_CLASS = '';

    private string $code;
    private string $lang;
    private string $theme;
    private string $title;
    private string $linenumbers;
    private string $class;
    private static bool $scriptLoaded = false;

    /**
     * コンストラクタ
     *
     * @param array $args 引数の配列
     */
    public function __construct(array $args)
    {
        $this->code = str_replace(["\r\n", "\r"], "\n", array_pop($args));
        $this->parseOptions($args);
    }

    /**
     * コードを変換してHTMLを返す
     *
     * @return string 変換後のHTML
     */
    public function convert(): string
    {
        $code = htmlsc($this->code);
        $lang = ' data-lang="' . $this->lang . '"';
        $hasLinenumbers = $this->linenumbers !== '';
        $theme = ' data-theme="' . $this->theme . '"';
        $class = $this->class !== '' ? $this->class : '';
        $title = $this->title !== '' ? ' data-title="' . $this->title . '"' : '';
        $linenumbers = $hasLinenumbers ? 'data-linenumbers' : '';
        $start = $hasLinenumbers ? '--start-index:' . $this->linenumbers . ';' : '';
        $script = $this->script();

        return <<<EOD
        <div class="plugin-shiki$class" style="visibility:hidden;$start"$lang$theme$title$linenumbers>
            <pre class="shiki-target">$code</pre>
        </div>
        $script
        EOD;
    }

    /**
     * JavaScriptのスクリプトを返す
     *
     * @return string スクリプトHTML
     */
    private function script(): string
    {
        if (self::$scriptLoaded) return '';

        self::$scriptLoaded = true;
        $cdn = self::CDN;
        $theme = self::DEFAULT_THEME;
        $lang = self::DEFAULT_LANG;
        $script = <<<EOD
        <script type="module">{js}</script>
        EOD;

        return $script;
    }

    /**
     * 引数を解析してプロパティを設定する
     *
     * @param array $args 引数の配列
     * @return void
     */
    private function parseOptions(array $args): void
    {
        $this->lang = self::DEFAULT_LANG;
        $this->theme = self::DEFAULT_THEME;
        $this->title = self::DEFAULT_TITLE;
        $this->linenumbers = self::DEFAULT_LINENUMBERS;
        $this->class = self::DEFAULT_CLASS;

        foreach ($args as $arg) {
            $arg = htmlsc($arg);
            [$key, $value] = array_map('trim', explode('=', $arg, 2));

            if ($key === 'lang') {
                $this->lang = $value;
            } elseif ($key === 'theme') {
                $this->theme = $value;
            } elseif ($key === 'title' && $value !== null) {
                $this->title = $value;
            } elseif ($key === 'linenumbers' || $key === 'start') {
                $value = is_numeric($value) ? $value : '1';
                $this->linenumbers = $value;
            } elseif ($key === 'class') {
                $this->class .= ' ' . $value;
            } elseif ($key === 'diff') {
                $this->class .= ' ' . 'diff-highlight';
            } else {
                $this->lang = $arg; // キーなしは言語指定とみなす
            }
        }
    }
}