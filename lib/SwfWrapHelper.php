<?php
/**
* SWF合成ヘルパークラス
*
* SWF素材ファイルに変数、画像を差し替えを行い出力する
*
* @author SUSH
* @version 1
*
* @extend Media_SWF
* @extend IO_Bit
**/

set_include_path(get_include_path().PATH_SEPARATOR.dirname(__FILE__) . '/');

require_once 'Media/SWF.class.php';
require_once 'Media/SWF/Builder.class.php';
require_once 'Media/SWF/Bitmap.class.php';
require_once 'Media/SWF/Builder.class.php';
require_once 'Media/SWF/Dumper.class.php';
require_once 'Media/SWF/Parser.class.php';
require_once 'Media/SWF/Tag.class.php';
require_once 'Media/SWF/Bitmap/GIF.class.php';
require_once 'Media/SWF/Bitmap/JPEG.class.php';
require_once 'Media/SWF/Bitmap/PNG.class.php';
require_once 'Media/SWF/Tag/DefineShape.class.php';
require_once 'Media/SWF/Tag/DefineSprite.class.php';
require_once 'Media/SWF/Tag/DoAction.class.php';
require_once 'Media/SWF/Tag/PlaceObject.class.php';
require_once 'Media/SWF/Tag/PlaceObject2.class.php';
require_once 'Media/SWF/Tag/RemoveObject.class.php';

class SwfWrapHelper {
    private
        $_file   = NULL,
        $_builder = NULL,
        $_root   = NULL;

    public function __construct( $file ) {
        if(! file_exists( $file ) ) {
            throw new Exception( 'SWF file is not found : ' . $file );
        }

        $this->_file = $file;
        $this->_builder = new Media_SWF_Builder();
        $this->_builder->parse( file_get_contents( $this->_file ) );

        // インスタンス名でデータを取り出す為のデータを解析
        // 上手く取得できないのでパフォーマンスを考慮してコメントアウト
        // $this->_builder->loadCharacterMap();
    }

    /**
     * 変数の埋め込み
     *
     * @access public
     * @param String $key 変数名
     * @param String $val データ
     * @return Void
     */
    public function set_master_var( $key, $val ) {
        // rootの最初のアクション部を取り出す
        if( $this->_root === NULL )
            $this->_root = $this->_builder->getFirstAction();
        if(! $this->_root->insertValue( $key, $val ) ) {
            $this->_root->dump('');
            throw new Exception( 'Set value is Failed : '. $key );
        }
    }

    /**
     * 変数の埋め込み
     *   連想配列を渡して一気に登録する
     *
     * @access public
     * @param Array $object 変数群
     * @return Void
     */
    public function set_master_var_array( $object ) {
        if(! is_array( $object ) && ! is_object( $object ) ) {
            throw new Exception( 'Need `Array` or `Object` : ' . $object );
        }
        foreach( $object as $key => $val ) {
            $this->set_master_var( $key, $val );
        }
    }

    /**
     * 画像の差し替え
     *   拡張子だけで判別してるので、適当なファイルだとエラーが返ってくるかも
     *   各画像のIDの調査方法は不明です。トライアンドエラーで差し替える画像を探すしかないかも。
     *
     * @access public
     * @param Integer $target_id 画像ID
     * @param String $image_file 差し替える画像ファイルパス
     * @return Void
     */
    public function replace_image( $target_id, $image_file ) {
        if(! file_exists( $image_file ) ) {
            throw new Exception( 'Image file is not found : ' . $image_file );
        }

        $image = NULL;

        // 拡張子を取得
        $path_arr = explode( '.', $image_file );
        $ext = end( $path_arr );
        switch( $ext ) {
            case 'jpg':
            case 'jpeg':
                $image = $this->replace_jpeg( $image_file );
            break;
            case 'gif':
                $image = $this->replace_gif( $image_file );
            break;
            case 'png':
                $image = $this->replace_png( $image_file );
            break;
            default:
                throw new Exception( 'File is Unknown File Type.' );
            break;
        }

        // 上手くIDが取得出来ないので直接指定に変更
        // $bitmap_id = $this->get_bitmap_id( $target_id );
        $bitmap_id = $target_id;

        // 生成したデータの埋め込み
        $this->_builder->setTagByCharacterId($bitmap_id, $image->getTag($bitmap_id));
    }

        /**
         * JPEG画像の差し替え
         *
         * @access private
         * @param String $jpeg_file 差し替えるJPEG画像ファイルパス
         * @return Media_SWF_Bitmap_JPEG
         */
        private function replace_jpeg( $jpeg_file ) {
            // JPEGからSWFに埋め込むデータを作成
            $image = new Media_SWF_Bitmap_JPEG( $jpeg_file );
            $image->build();

            return $image;
        }

        /**
         * GIF画像の差し替え
         *
         * @access private
         * @param String $gif_file 差し替えるGIF画像ファイルパス
         * @return Media_SWF_Bitmap_GIF
         */
        private function replace_gif( $gif_file ) {
            // GIFからSWFに埋め込むデータを作成
            $image = new Media_SWF_Bitmap_GIF( $gif_file );
            $image->build();

            return $image;
        }

        /**
         * PNG画像の差し替え
         *
         * @access private
         * @param String $png_file 差し替えるPNG画像ファイルパス
         * @return Media_SWF_Bitmap_PNG
         */
        private function replace_png( $png_file ) {
            // PNGからSWFに埋め込むデータを作成
            $image = new Media_SWF_Bitmap_PNG( $png_file );
            $image->build();

            return $image;
        }

        /**
         * スプライト名からIDを取得…のはずだったんですが上手く動いていません
         *
         * @access private
         * @param String $target_id スプライト名
         * @return Integer
         */
        private function get_bitmap_id( $target_id ) {
            return $this->_builder->getBitmapIdBySpriteName( $target_id );
        }

    /**
     * SWFデータを生成
     *
     * @access public
     * @return Binary
     */
    public function build() {
        return $this->_builder->build();
    }

    /**
     * SWFデータを表示
     *
     * @access public
     * @return Void
     */
    public function view() {
        $content = $this->build();

        // ヘッダー
        $size = strlen( $content );
        header("Content-Length: $size");
        header("Cache-Control: no-cache, must-revalidate");
        header("Content-type: application/x-shockwave-flash");

        echo $content;
    }

    /**
     * SWFタグ内容を表示する（デバッグ用機能）
     *
     * @access public
     * @return Void
     */
    public function dump() {
        echo '<pre>';
        $this->_builder->dump();
        echo '</pre>';
    }
}