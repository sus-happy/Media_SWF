## 概要

FlashLite1.1のswfを動的に作り出すためのPHPライブラリ[Media_SWF](https://github.com/ken39arg/Media_SWF)を自分用に色々と触ったものです。

## 依存

- IO_Bit
  http://openpear.org/package/IO_Bit

## はじめに

読み込むファイルは一つのみです。その他のファイルはそこから読み込みます。
使っていないファイルも読み込んでしまうので、パフォーマンス的にはイマイチかもしれませんが…。

    require_once( 'path/to/SwfWrapHelper.php' );

まず、書き換えたいswfを入力補助クラスに渡します。

    $swf = new SwfWrapHelper( 'path/to/movie.swf' );

単純にswfを書き換えたデータを取得したいだけの場合は、

    $content = $swf->build();

そのまま表示まで行う場合は、

    $swf->view();

のように書きます。
「view」の場合は、「build」も一緒に実行しますので、「build」->「view」のように実行する必要はありません。

## 変数を挿入する

読み込んだswfの「1フレーム目の先頭」に変数を挿入するメソッドです。もし、既に1フレーム目に同名の変数が存在する場合は値を書き換えます、たぶん。（試してませんが）

    $swf->set_master_var( 'key', 'var' );

変数名「key」に「var」という値が入ります。

また、一気に値を追加したい場合は、

    $vars = array(
        'hoge' => 'fuga',
        'foo' => 'bar'
    );
    $swf->set_master_var_array( $vars );

というメソッドも用意してあります。

## 画像を差し替える

swf内で利用されている画像を差し替える際に、利用している各画像のIDを渡します。ですが、このIDを何処で調べることが出来るのか良く分かっていません。
タイムライン上に出現する順番にIDが割り振られているんじゃないかと思うんですが…。調べる方法を見つけたら追記します。

実際の書き方はこんな感じです。

    // $id = swf内の画像ID
    $swf->replace_image( $id, 'path/to/image.jpg' );
    画像形式はJPG、PNG、GIFに対応していて、拡張子で判別しています。
    逆に言うと拡張子でしか判別していないので、動的に画像データを渡す際は気をつける必要があります。

## 一連の記述例

    <?php
    require_once( 'path/to/SwfWrapHelper.php' );

    // swfファイルの読み込み
    $swf = new SwfWrapHelper( 'path/to/movie.swf' );

    // 変数の挿入/置換
    $swf->set_master_var( 'key', 'val' );
    $vars = array(
        'hoge' => 'fuga',
        'foo' => 'bar'
    );
    $swf->set_master_var_array( $vars );

    // 画像の差し替え
    $swf->replace_image( $id, 'path/to/image.jpg' );

    // 書き換え後のswfの出力
    $swf->view();