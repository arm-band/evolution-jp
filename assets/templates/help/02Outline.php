<?php
if(IN_MANAGER_MODE!="true") die("<b>INCLUDE_ORDERING_ERROR</b><br /><br />Please use the MODx Content Manager instead of accessing this file directly.");
?>
<style type="text/css">
h3 {font-weight:bold;letter-spacing:2px;font-size:1;margin-top:10px;}
ul {margin-bottom:15px;}
</style>

<div class="sectionHeader">MODxの概要</div>
<div class="sectionBody" style="padding:10px 20px;">
<h3>コンテンツ管理の基本・リソース操作</h3>

<p>MODxでは「リソース」と呼ばれる単位でひとつのコンテンツを管理しています。リソースはベータ版時代のMODxでは「ドキュメント」と呼ばれていたもので、見た目の１ページをリソースとして管理しています。通常のWebページと同様に、ひとつのリソースは「タイトル」「内容」「コンテントタイプ」「METAタグ」などで構成されます。 </p>
<p>
MODxのコンテンツ管理はリソース操作が軸になっています。リソースの構成がそのままサイトの構成となります。管理画面左側の「サイトツリー」に表示されている任意のリソースを<b>右クリック</b>(またはアイコンを左クリック)するとコンテキストメニューが表示されますので、まずはここから操作の基本を習得してください。新規作成・編集・移動・削除・複製・公開・非公開などの各種操作ができます。</p>

<h3>リソース操作の応用</h3>

<p>拡張モジュール「Doc Manager」を同梱しており、管理画面メニューバーのモジュールメニューからアクセスできます。詳細条件によるリソース一括操作が必要な場合はお試しください。</p>

<h3>サイトの設定は「グローバル設定」で</h3>

<p>サイトの設定は管理画面メニューバーの「ツール → <a href="index.php?a=17">グローバル設定</a>」で行ないます。管理画面に関する設定もここに含まれます。グローバル設定の設定項目をひとつずつ確認することで、MODxで実現できるサイト運用はどのようなものなのか、おおまかなイメージをつかむことができます。</p>
<p>
グローバル設定の設定項目は、MODxが実際にその全ての機能を持っているとは限りません。設定を変更してもその違いを確認することができない項目が実際にいくつかあります。「アクセスログを記録」や「サーバータイプ」「サイト閉鎖中メッセージ」などがそれにあたり、これらはMODx本体の機能としては実装されていません。
</p>
<p>
MODx本体が実装していない機能は、プラグインやモジュールで補うか、リソースやテンプレートで呼び出して利用します。グローバル設定のほとんど全ての項目は、<b>「セッティング変数」</b>などのシンプルな記述によって、リソース・テンプレート・チャンク・スニペット・プラグインなど、MODxを構成するあらゆる「エレメント」から手軽に呼び出すことができます。リソースやテンプレートから呼び出されることが多いセッティング変数は[(site_name)]くらいで、他はスニペットやプラグインなどで値を呼んで処理を分岐させるような用途に活用できます。
</p>

<h3>オーサリングツール感覚のサイト管理</h3>

<p>ページのデザインはテンプレートとコンテンツに分離されています。ひととおりの設定を確認したら、実際に任意のリソースの編集画面を開いてみてください。実際の表示に近い状態でコンテンツを編集できます。テンプレートはテンプレート編集画面で編集します。</p>
<p>MODxでは、ページ単位(リソース単位)でテンプレートを割り当てることができます。また、テンプレートを使わないこともできます。たとえばトップページだけはテンプレートを使わずにリソースだけで自由に作る、といったこともできます。</p>
<p>この自由度の高さと引き換えに、カテゴリーごとのテンプレート設定など、普通のCMSなら当たり前にできることがMODxではできません。これに対応するために、前述の「Doc Manager」などの拡張モジュールを利用します。記事が増えてきたらフォルダを作ってまとめる、一時的に隠しておきたいページは隠しフォルダに退避させておくなど、アナログな運用感覚に基づいたサイト管理がしやすいCMSです。こうした基本操作の多くがマウス操作によるものです。</p>
<p>ルールが必要な場合は、テンプレート変数(※後述)やスニペットなどを利用して自らルールを構成します。シンプルなサイトを手軽に作れるのがMODxの特長のひとつですが、ちょっと難しいことをやってみようかという時、MODxはその先に進む道が豊富に用意されています。</p>

<h3>静的サイトをエミュレート</h3>
<p>
柔軟なURLコントロールとキャッシュコントロールを実装しており、htmlファイルを静的に配置しているサイトのように振る舞うことができます。サイトをまるごと静的htmlファイルとして書き出す機能もあります。</p>

<h3>チャンクとスニペット</h3>

<p>管理の基本であるリソースとテンプレートの組み合わせに加え、さらにMODxでは「チャンク」と呼ばれる仕組みを利用して、htmlコードの各部をパーツ化できます。チャンクはリソース・テンプレートのどちらからでも自由に呼び出すことができるので、サイト全体に共通するナビゲーションバーに利用することもできますし、スポットで用いる季節の広告バナーなどに利用することもできます。チャンクをひとつ書き換えるだけで、そのチャンクを読み込んでいる全てのリソースが更新されます。</p>

<p>また、チャンクと同様、リソースとテンプレートそれぞれから自由に呼び出せる「スニペット」と呼ばれる仕組みを持ちます。チャンクと違い、phpのコードを書いて実行することができるため、ブログパーツ的な利用が可能です。既存のオーサリングツールと比べ、MODxでは手軽に動的な実装を実現できます。</p>

<p>チャンク・スニペットともに、リソース・テンプレートのそれぞれから同じように呼び出すことができます。また、チャンク・スニペット自体も、それぞれの中から自由に呼び出すことができます。入れ子による呼び出しも可能です。これらは MODxでは「エレメント」と呼ばれ、無限の組み合わせを持つことができます。アイデア次第で、あらゆるサイトを作ることができます。</p>


<h3>テンプレート変数</h3>
<p>さらにMODxでは、基本の構成単位であるリソースにも自由を与えました。「テンプレート変数」と呼ばれるものがそれで、「タイトル」「内容本文」などの基本的な入力項目に加え、MODxでは自由に項目を拡張することができます。この特長により、MODxは単なるオーサリングツールとしての枠を超えて、柔軟性の高いデータベースアプリケーションとしての活用も可能となっています。他のCMSではカスタムフィールドなどと呼ばれるものと同様ですが、MODxのテンプレート変数は入力・出力のそれぞれに多様な工夫を施すことができ、非常に強力です。</p>

<h3>プラグイン</h3>

<p>チャンク・スニペット・テンプレート変数は、MODxをオーサリングツールまたはWebアプリケーションとして活用するために欠かせないものであり、実装の形としてはhtmlコードが対象である点が共通しています。これに対しプラグインは、CMSとしてのMODxの機能に関連付けられます。たとえばリソースの出力全体に対して、特定の文字列を対象に置換したり、ある条件によってアップロードされた画像に対し特殊な拡大縮小の処理を加えたりできます。また、管理画面に対する機能拡張を加えることもできます。</p>

<h3>「エレメント」の管理</h3>

<p>管理画面メニューバーの「エレメント」→ <a href="index.php?a=76">「エレメント管理」</a>をご覧ください。「テンプレート」「テンプレート変数」「チャンク」「スニペット」「プラグイン」などを一覧できます。また手軽に編集・作成・削除・複製できます。お気軽にお試しください。数文字書き換えてみて、意図どおりに動かなければ元に戻せばいいだけです。</p>

<h3>モジュール</h3>

<p>専用の管理画面を持つことをできるようにしたものが<a href="index.php?a=106">「モジュール」</a>と呼ばれる仕組みです。モジュール自体は、html上の記述や任意のイベントをトリガーとするスニペットやプラグインのような実行はできません。該当モジュールの管理画面を開いた時のみ、コードが実行されます。複数のスニペットやプラグインを束ねてモジュールの機能の一部とできるため、MODxのアドオン実装としては最も高い拡張性を持つと考えることができます。管理に優れた高機能なモジュールを軸として、MODxを特定の目的に特化したWebアプリとすることもできるでしょう。</p>
<p>
モジュールそれ自体は管理機能のみを提供します。スニペットやプラグインに対し個別に管理画面を設けるのではなく、モジュールを通じてコントロールすることで柔軟性の高さとシンプルさを両立しています。<br />
※ただしプラグインに関しては簡易の設定タブがあります。パラメータのオンオフ程度の設定であれば十分でしょう。</p>

<h3>ウェブリンク</h3>
<p>
ウェブリンクを用いると、MODx以外で作られたページをリンクを通じてシームレスにサイトに取り込むことができます。実質的にはリンクを張るだけなのでデザインやコンテンツの制御まではできませんが、MODxでコントロールするナビゲーションの構成要素に含めることができるため便利です。また、MODxで作られた既存のリソースの別名として配置するような使い方もできます。
</p>

<h3>ダッシュボードとヘルプのカスタマイズ</h3>
<p>
/assets/templates/manager/welcome.htmlを独自にカスタマイズできます。これは管理画面にログインした時に最初に表示される<a href="index.php?a=2">ダッシュボード</a>にあたるファイルです。このファイルはコア領域に属するものではないので、サイト運用の目的に応じて自由にカスタマイズできます。また、現在ご覧いただいているこの「ヘルプ」もカスタマイズ自由なエレメント領域に設置されています。つまり、個別の案件に応じたオンラインヘルプの同梱も簡単に実現できます。御社の電話番号・担当者名・サポート期間・その他保守条件などを記述しておくとよいでしょう。</p>

<h3>リソースの概念</h3>
<p>
通常のWebページだけでなく、コンテント設定によりスタイルシート・RSS/XMLファイル・PDFファイル・Wordファイル・Excelファイルなどを自由にタイプを規定し表現することが理論的には可能になっています。スタイルシートやRSSフィードの管理は現時点でもノウハウが確立されています。PDFファイルやExcelファイルを動的に生成するphpライブラリを利用するなどすれば、さらに応用が広がります。</p>
</div>
