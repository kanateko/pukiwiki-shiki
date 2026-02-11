<?php
/**
 * Shiki Syntax Highlighter を使用してコードを表示するプラグイン
 *
 * @version 1.0.0
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
        <div class="plugin-shiki$class" style="visibility:hidden;$start"$start$lang$theme$title$linenumbers>
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
        <script type="module">import{createHighlighter as e}from"$cdn";if(!document._shiki_css_loaded){const e=document.createElement("style");e.textContent=".plugin-shiki{--padding-sm:4px;--padding-md:calc(var(--padding-sm) * 2);--padding-lg:calc(var(--padding-sm) * 4);--font-size-label:0.75rem;border-radius:8px;margin-bottom:24px;opacity:0;overflow:hidden;position:relative}.plugin-shiki:hover .shiki-button{opacity:1}.plugin-shiki:hover .shiki-lang{opacity:0}.plugin-shiki:has(.shiki){opacity:1;transition:opacity .3s}.plugin-shiki:has(.shiki-title) .shiki{padding-top:calc(var(--padding-lg) + var(--font-size-label))}.plugin-shiki .shiki{border-radius:0!important;box-shadow:none;margin-bottom:0;padding:var(--padding-lg);white-space:pre!important}.plugin-shiki .shiki code{display:block;min-width:-webkit-fill-available;min-width:-moz-available;min-width:stretch;width:-moz-fit-content;width:fit-content}.plugin-shiki .shiki .diff{display:inline-block;width:-webkit-fill-available;width:-moz-available;width:stretch}.plugin-shiki .shiki .diff.added{background-color:rgba(0,255,0,.12)}.plugin-shiki .shiki .diff.removed{background-color:rgba(255,0,0,.12)}.plugin-shiki[data-linenumbers] .shiki{padding-left:0}.plugin-shiki[data-linenumbers] .shiki code{counter-increment:line calc(var(--start-index,1) - 1);counter-reset:line}.plugin-shiki[data-linenumbers] .shiki code .line::before{background-color:var(--theme-bgcolor);border-right:1px solid gray;color:var(--theme-color);content:counter(line);counter-increment:line;display:inline-block;left:0;padding-right:.5rem;position:sticky;text-align:right;width:3rem}.shiki-wrapper{background-color:var(--theme-bgcolor);color:var(--theme-color)}.shiki-label{color:var(--theme-color);font-size:var(--font-size-label);font-style:italic;line-height:var(--font-size-label);position:absolute;top:var(--padding-md)}.shiki-lang{right:var(--padding-md);transition:opacity .3s}.shiki-title{left:var(--padding-md)}.shiki-button{background:url(data:image/svg+xml,%3Csvg%20xmlns%3D%22http%3A%2F%2Fwww.w3.org%2F2000%2Fsvg%22%20%20%20%20%20viewBox%3D%220%200%2024%2024%22%20%20%20%20%20fill%3D%22none%22%20%20%20%20%20stroke%3D%22%23a0a0a0%22%20%20%20%20%20stroke-width%3D%222%22%20%20%20%20%20stroke-linecap%3D%22round%22%20%20%20%20%20stroke-linejoin%3D%22round%22%20%20%20%20%20aria-hidden%3D%22true%22%3E%3Crect%20x%3D%229%22%20y%3D%222%22%20width%3D%226%22%20height%3D%224%22%20rx%3D%221%22%2F%3E%3Cpath%20d%3D%22M9%204H7a2%202%200%200%200-2%202v14a2%202%200%200%200%202%202h10a2%202%200%200%200%202-2V6a2%202%200%200%200-2-2h-2%22%2F%3E%3C%2Fsvg%3E) center/24px no-repeat #373737;border:2px solid #575757;border-radius:6px;height:40px;opacity:0;position:absolute;right:var(--padding-md);top:var(--padding-md);transition:opacity .3s,border-color .3s,background-color .3s;width:40px}.shiki-button.disabled{background-color:#324432;background-image:url(data:image/svg+xml,%3Csvg%20xmlns%3D%22http%3A%2F%2Fwww.w3.org%2F2000%2Fsvg%22%20%20%20%20%20viewBox%3D%220%200%2024%2024%22%20%20%20%20%20fill%3D%22none%22%20%20%20%20%20stroke%3D%22%2376ca76%22%20%20%20%20%20stroke-width%3D%222%22%20%20%20%20%20stroke-linecap%3D%22round%22%20%20%20%20%20stroke-linejoin%3D%22round%22%20%20%20%20%20aria-hidden%3D%22true%22%3E%3Crect%20x%3D%229%22%20y%3D%222%22%20width%3D%226%22%20height%3D%224%22%20rx%3D%221%22%2F%3E%3Cpath%20d%3D%22M9%204H7a2%202%200%200%200-2%202v14a2%202%200%200%200%202%202h10a2%202%200%200%200%202-2V6a2%202%200%200%200-2-2h-2%22%2F%3E%3Cpath%20d%3D%22M9%2013l2%202%204-4%22%2F%3E%3C%2Fsvg%3E);border-color:#539253;pointer-events:none;transition:border-color,background-color}",document.head.appendChild(e),document._shiki_css_loaded=!0}const i=async(e,i)=>{const t="$lang";if(!i)return t;try{return e.getLoadedLanguages().includes(i)||await e.loadLanguage(i),i}catch(e){return t}},t=async(e,i)=>{const t="$theme";if(!i)return t;try{return e.getLoadedThemes().includes(i)||await e.loadTheme(i),i}catch(e){return t}},a=e=>{const i=e.closest(".plugin-shiki").querySelector("code");navigator.clipboard.writeText(i.textContent).then(()=>{e.classList.toggle("disabled"),setTimeout(()=>{e.classList.toggle("disabled")},1200)})},o=(e,i)=>({preprocess:e=>e.trimEnd(),pre(t){const a=t.properties.style.match(/background-color:([^;]+);color:([^;]+)/),o=a[1],n=a[2];let r=[];return i&&r.push({type:"element",tagName:"span",properties:{className:"shiki-title shiki-label"},children:[{type:"text",value:i}]}),r=r.concat([{type:"element",tagName:"span",properties:{className:"shiki-lang shiki-label"},children:[{type:"text",value:e}]},{type:"element",tagName:"button",properties:{className:"shiki-button",title:"コピー"}},t]),{type:"element",tagName:"div",properties:{className:"shiki-wrapper",style:"--theme-bgcolor:"+o+";--theme-color:"+n+";"},children:r}}}),n=()=>({line(e){const i=e.children?e.children[0]:null,t=(e=>{const i=e&&e.children?e.children[0]:null,t=i&&"text"===i.type?i.value.trim():"";return t?t.charAt(0):""})(i);"+"!==t&&"-"!==t||(i.properties.style=null,this.addClassToHast(e,"diff"),"+"===t?this.addClassToHast(e,"added"):this.addClassToHast(e,"removed"))}});document.addEventListener("DOMContentLoaded",async()=>{const r=await e({themes:["$theme"],langs:[]}),s=document.querySelectorAll(".plugin-shiki");for(const e of s){const s=e.querySelector("pre"),l=await i(r,e.dataset.lang),d=await t(r,e.dataset.theme),c=s.textContent,h=e.dataset.title,p=[o(l,h)];e.classList.contains("diff-highlight")&&p.push(n());const g=await r.codeToHtml(c,{lang:l,theme:d,transformers:p});e.innerHTML=g,e.style.visibility="visible",e.querySelector("button").addEventListener("click",e=>{a(e.currentTarget)})}});</script>
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