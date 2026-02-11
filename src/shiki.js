import { createHighlighter } from '$cdn';

// CSSファイルを読み込む
if (!document._shiki_css_loaded) {
    const css = document.createElement('style');
    css.textContent = '{css}';
    document.head.appendChild(css);
    document._shiki_css_loaded = true;
}

// 言語が解決されていることを確認する
const resolveLang = async (highlighter, lang) => {
    const defaultLang = '$lang';
    if (!lang) return defaultLang;

    try {
        if (!highlighter.getLoadedLanguages().includes(lang)) {
            await highlighter.loadLanguage(lang);
        }
        return lang;
    } catch (e) {
        return defaultLang;
    }
}

// テーマが解決されていることを確認する
const resolveTheme = async (highlighter, theme) => {
    const defaultTheme = '$theme';
    if (!theme) return defaultTheme;

    try {
        if (!highlighter.getLoadedThemes().includes(theme)) {
            await highlighter.loadTheme(theme);
        }
        return theme;
    } catch (e) {
        return defaultTheme;
    }
}

// コードをクリップボードにコピーする
const copyToClipboard = (target) => {
    const parent = target.closest('.plugin-shiki');
    const code = parent.querySelector('code');
    navigator.clipboard.writeText(code.textContent).then(() => {
        target.classList.toggle('disabled');
        setTimeout(() => {
            target.classList.toggle('disabled');
        }, 1200);
    });
}

// コードブロックの前にラベルやボタンを挿入するTransformer
const insertHeaderTransformer = (lang, title) => {
    return {
        preprocess(text) {
            return text.trimEnd();
        },
        pre(node) {
            const themeStyle = node.properties.style;
            const found = themeStyle.match(/background-color:([^;]+);color:([^;]+)/);
            const bgColor = found[1];
            const txtColor = found[2];
            let children = [];

            if (title) children.push({
                type: 'element',
                tagName: 'span',
                properties: { className: 'shiki-title shiki-label' },
                children: [{ type: 'text', value: title }]
            })

            children = children.concat([
                {
                    type: 'element',
                    tagName: 'span',
                    properties: { className: 'shiki-lang shiki-label' },
                    children: [{ type: 'text', value: lang }]
                },
                {
                    type: 'element',
                    tagName: 'button',
                    properties: {
                        className: 'shiki-button',
                        title: 'コピー',
                    }
                },
                node
            ]);

            node = {
                type: 'element',
                tagName: 'div',
                properties: {
                    className: 'shiki-wrapper',
                    style: '--theme-bgcolor:' + bgColor + ';--theme-color:' + txtColor + ';'
                },
                children: children
            }

            return node;
        },
    }
}

// トークンの最初の一文字を取得
const getFirstCharFromToken = (firstToken) => {
    const firstChild = firstToken && firstToken.children ? firstToken.children[0] : null;
    const textContent = firstChild && firstChild.type === 'text' ? firstChild.value.trim() : '';

    return textContent ? textContent.charAt(0) : '';
}

// 追加・削除行にクラスを追加するTransformer
const highlightDiffTransformer = () => {
    return {
        line(node) {
            const firstToken = node.children ? node.children[0] : null;
            const firstChar = getFirstCharFromToken(firstToken);

            if (firstChar === '+' || firstChar === '-') {
                firstToken.properties.style = null;
                this.addClassToHast(node, 'diff');
                if (firstChar === '+') this.addClassToHast(node, 'added');
                else this.addClassToHast(node, 'removed');
            }
        }
    }
}

// DOMが完全に読み込まれたときに実行される
document.addEventListener('DOMContentLoaded', async () => {
    const highlighterInstance = await createHighlighter({
        themes: ['$theme'],
        langs: [],
    });
    const elements = document.querySelectorAll('.plugin-shiki');

    // plugin-shikiクラスを持つ各要素を処理する
    for (const element of elements) {
        const target = element.querySelector('pre')
        const lang = await resolveLang(highlighterInstance, element.dataset.lang);
        const theme = await resolveTheme(highlighterInstance, element.dataset.theme);
        const text = target.textContent;
        const title = element.dataset.title;
        const transformers = [
            insertHeaderTransformer(lang, title)
        ];

        // 差分のハイライト
        if (element.classList.contains('diff-highlight')) {
            transformers.push(highlightDiffTransformer());
        }

        // コードをハイライトし、ヘッダー要素を挿入する
        const html = await highlighterInstance.codeToHtml(text, {
            lang: lang,
            theme: theme,
            transformers: transformers,
        });
        element.innerHTML = html;
        element.style.visibility = 'visible';

        // コードをクリップボードにコピーするボタンを追加する
        const button = element.querySelector('button');
        button.addEventListener('click', event => {
            copyToClipboard(event.currentTarget);
        });
    }
});
