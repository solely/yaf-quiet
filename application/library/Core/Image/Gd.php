<?php
/**
 * FileName： Gd.php
 * Author: solely
 * DateTime: 2016/11/6 18:05
 * Description:     Gd处理图像
 */
namespace Core\Image;

use Core\Image;
use Yaf\Exception;

class Gd
{
    /**
     * 图像资源对象
     * @var resource
     */
    private $img;

    /**
     * 图像信息，包括width,height,type,mime,size
     * @var array
     */
    private $info;

    /**
     * 构造方法，可用于打开一张图像
     * @param string $imgname 图像路径
     */
    public function __construct($imgname = null)
    {
        $imgname && $this->open($imgname);
    }

    /**
     * 打开一张图像
     * @param $imgname      图像路径
     * @throws Exception
     */
    public function open($imgname)
    {
        //检测图像文件
        if (!is_file($imgname))
            throw new Exception ('不存在的图像文件');

        //获取图像信息
        $info = getimagesize($imgname);

        //检测图像合法性
        if (false === $info || (IMAGETYPE_GIF === $info[2] && empty($info['bits']))) {
            throw new Exception ('非法图像文件');
        }

        //设置图像信息
        $this->info = array(
            'width' => $info[0],
            'height' => $info[1],
            'type' => image_type_to_extension($info[2], false),
            'mime' => $info['mime'],
        );

        //销毁已存在的图像
        empty($this->img) || imagedestroy($this->img);

        //打开图像
        if ('gif' == $this->info['type']) {
            $class = 'Gif';
            $this->gif = new $class($imgname);
            $this->img = imagecreatefromstring($this->gif->image());
        } else {
            $fun = "imagecreatefrom{$this->info['type']}";
            $this->img = $fun($imgname);
        }
    }

    /**
     * 保存图像
     * @param $imgname          图像保存名称
     * @param null $type 图像类型
     * @param int $quality 图像质量
     * @param bool $interlace 是否对JPEG类型图像设置隔行扫描
     * @throws Exception
     */
    public function save($imgname, $type = null, $quality = 80, $interlace = true)
    {
        if (empty($this->img))
            throw new Exception ('没有可以被保存的图像资源');

        //自动获取图像类型
        if (is_null($type)) {
            $type = $this->info['type'];
        } else {
            $type = strtolower($type);
        }
        //保存图像
        if ('jpeg' == $type || 'jpg' == $type) {
            //JPEG图像设置隔行扫描
            imageinterlace($this->img, $interlace);
            imagejpeg($this->img, $imgname, $quality);
        } elseif ('gif' == $type && !empty($this->gif)) {
            $this->gif->save($imgname);
        } else {
            $fun = 'image' . $type;
            $fun($this->img, $imgname);
        }
    }

    /**
     * 返回图像宽度
     * @return mixed    图像宽度
     * @throws Exception
     */
    public function width()
    {
        if (empty($this->img))
            throw new Exception ('没有指定图像资源');
        return $this->info['width'];
    }

    /**
     * 返回图像高度
     * @return mixed    图像高度
     * @throws Exception
     */
    public function height()
    {
        if (empty($this->img))
            throw new Exception ('没有指定图像资源');
        return $this->info['height'];
    }

    /**
     * 返回图像类型
     * @return mixed  图像类型
     * @throws Exception
     */
    public function type()
    {
        if (empty($this->img))
            throw new Exception ('没有指定图像资源');
        return $this->info['type'];
    }

    /**
     * 返回图像MIME类型
     * @return mixed  图像MIME类型
     * @throws Exception
     */
    public function mime()
    {
        if (empty($this->img))
            throw new Exception ('没有指定图像资源');
        return $this->info['mime'];
    }

    /**
     * 返回图像尺寸数组 0 - 图像宽度，1 - 图像高度
     * @return array  图像尺寸
     * @throws Exception
     */
    public function size()
    {
        if (empty($this->img))
            throw new Exception ('没有指定图像资源');
        return array($this->info['width'], $this->info['height']);
    }

    /**
     * 裁剪图像
     * @param $w  裁剪区域宽度
     * @param $h  裁剪区域高度
     * @param int $x 裁剪区域x坐标
     * @param int $y 裁剪区域y坐标
     * @param null $width 图像保存宽度
     * @param null $height 图像保存高度
     * @throws Exception
     */
    public function crop($w, $h, $x = 0, $y = 0, $width = null, $height = null)
    {
        if (empty($this->img))
            throw new Exception ('没有可以被裁剪的图像资源');

        //设置保存尺寸
        empty($width) && $width = $w;
        empty($height) && $height = $h;

        do {
            //创建新图像
            $img = imagecreatetruecolor($width, $height);
            // 调整默认颜色
            $color = imagecolorallocate($img, 255, 255, 255);
            imagefill($img, 0, 0, $color);

            //裁剪
            imagecopyresampled($img, $this->img, 0, 0, $x, $y, $width, $height, $w, $h);
            imagedestroy($this->img); //销毁原图
            //设置新图像
            $this->img = $img;
        } while (!empty($this->gif) && $this->gifNext());

        $this->info['width'] = $width;
        $this->info['height'] = $height;
    }

    /**
     * 生成缩略图，加$src_x、$src_y这两个参数是为了解决299行imagecopyresampled这个函数有传入的参数未定义的bug
     * @param $width  缩略图最大宽度
     * @param $height  缩略图最大高度
     * @param int $type 缩略图裁剪类型
     * @param int $src_x 源的 X 坐标点
     * @param int $src_y 源的 Y 坐标点
     * @throws Exception
     */
    public function thumb($width, $height, $type = Image::IMAGE_THUMB_SCALE, $src_x = 0, $src_y = 0)
    {
        if (empty($this->img))
            throw new Exception ('没有可以被缩略的图像资源');

        //原图宽度和高度
        $w = $this->info['width'];
        $h = $this->info['height'];

        /* 计算缩略图生成的必要参数 */
        switch ($type) {
            /* 等比例缩放 */
            case Image::IMAGE_THUMB_SCALE:
                //原图尺寸小于缩略图尺寸则不进行缩略
                if ($w < $width && $h < $height)
                    return;

                //计算缩放比例
                $scale = min($width / $w, $height / $h);

                //设置缩略图的坐标及宽度和高度
                $x = $y = 0;
                $width = $w * $scale;
                $height = $h * $scale;
                break;

            /* 居中裁剪 */
            case Image::IMAGE_THUMB_CENTER:
                //计算缩放比例
                $scale = max($width / $w, $height / $h);

                //设置缩略图的坐标及宽度和高度
                $w = $width / $scale;
                $h = $height / $scale;
                $x = ($this->info['width'] - $w) / 2;
                $y = ($this->info['height'] - $h) / 2;
                break;

            /* 左上角裁剪 */
            case Image::IMAGE_THUMB_NORTHWEST:
                //计算缩放比例
                $scale = max($width / $w, $height / $h);

                //设置缩略图的坐标及宽度和高度
                $x = $y = 0;
                $w = $width / $scale;
                $h = $height / $scale;
                break;

            /* 右下角裁剪 */
            case Image::IMAGE_THUMB_SOUTHEAST:
                //计算缩放比例
                $scale = max($width / $w, $height / $h);

                //设置缩略图的坐标及宽度和高度
                $w = $width / $scale;
                $h = $height / $scale;
                $x = $this->info['width'] - $w;
                $y = $this->info['height'] - $h;
                break;

            /* 填充 */
            case Image::IMAGE_THUMB_FILLED:
                //计算缩放比例
                if ($w < $width && $h < $height) {
                    $scale = 1;
                } else {
                    $scale = min($width / $w, $height / $h);
                }

                //设置缩略图的坐标及宽度和高度
                $neww = $w * $scale;
                $newh = $h * $scale;
                $posx = ($width - $w * $scale) / 2;
                $posy = ($height - $h * $scale) / 2;

                do {
                    //创建新图像
                    $img = imagecreatetruecolor($width, $height);
                    // 调整默认颜色
                    $color = imagecolorallocate($img, 255, 255, 255);
                    imagefill($img, 0, 0, $color);

                    //裁剪
                    imagecopyresampled($img, $this->img, $posx, $posy, $src_x, $src_y, $neww, $newh, $w, $h);
                    imagedestroy($this->img); //销毁原图
                    $this->img = $img;
                } while (!empty($this->gif) && $this->gifNext());

                $this->info['width'] = $width;
                $this->info['height'] = $height;
                return;

            /* 固定 */
            case Image::IMAGE_THUMB_FIXED:
                $x = $y = 0;
                break;

            default:
                throw new Exception ('不支持的缩略图裁剪类型');
        }

        /* 裁剪图像 */
        $this->crop($w, $h, $x, $y, $width, $height);
    }

    /**
     * 添加水印
     * @param $source  水印图片路径
     * @param int $locate 水印位置
     * @param int $alpha 水印透明度
     * @throws Exception
     */
    public function water($source, $locate = Image::IMAGE_WATER_SOUTHEAST, $alpha = 80)
    {
        //资源检测
        if (empty($this->img))
            throw new Exception ('没有可以被添加水印的图像资源');
        if (!is_file($source))
            throw new Exception ('水印图像不存在');

        //获取水印图像信息
        $info = getimagesize($source);
        if (false === $info || (IMAGETYPE_GIF === $info[2] && empty($info['bits']))) {
            throw new Exception ('非法水印文件');
        }

        //创建水印图像资源
        $fun = 'imagecreatefrom' . image_type_to_extension($info[2], false);
        $water = $fun($source);

        //设定水印图像的混色模式
        imagealphablending($water, true);

        /* 设定水印位置 */
        switch ($locate) {
            /* 右下角水印 */
            case Image::IMAGE_WATER_SOUTHEAST:
                $x = $this->info['width'] - $info[0];
                $y = $this->info['height'] - $info[1];
                break;

            /* 左下角水印 */
            case Image::IMAGE_WATER_SOUTHWEST:
                $x = 0;
                $y = $this->info['height'] - $info[1];
                break;

            /* 左上角水印 */
            case Image::IMAGE_WATER_NORTHWEST:
                $x = $y = 0;
                break;

            /* 右上角水印 */
            case Image::IMAGE_WATER_NORTHEAST:
                $x = $this->info['width'] - $info[0];
                $y = 0;
                break;

            /* 居中水印 */
            case Image::IMAGE_WATER_CENTER:
                $x = ($this->info['width'] - $info[0]) / 2;
                $y = ($this->info['height'] - $info[1]) / 2;
                break;

            /* 下居中水印 */
            case Image::IMAGE_WATER_SOUTH:
                $x = ($this->info['width'] - $info[0]) / 2;
                $y = $this->info['height'] - $info[1];
                break;

            /* 右居中水印 */
            case Image::IMAGE_WATER_EAST:
                $x = $this->info['width'] - $info[0];
                $y = ($this->info['height'] - $info[1]) / 2;
                break;

            /* 上居中水印 */
            case Image::IMAGE_WATER_NORTH:
                $x = ($this->info['width'] - $info[0]) / 2;
                $y = 0;
                break;

            /* 左居中水印 */
            case Image::IMAGE_WATER_WEST:
                $x = 0;
                $y = ($this->info['height'] - $info[1]) / 2;
                break;

            default:
                /* 自定义水印坐标 */
                if (is_array($locate)) {
                    list($x, $y) = $locate;
                } else {
                    throw new Exception ('不支持的水印位置类型');
                }
        }

        do {
            //添加水印
            $src = imagecreatetruecolor($info[0], $info[1]);
            // 调整默认颜色
            $color = imagecolorallocate($src, 255, 255, 255);
            imagefill($src, 0, 0, $color);

            imagecopy($src, $this->img, 0, 0, $x, $y, $info[0], $info[1]);
            imagecopy($src, $water, 0, 0, 0, 0, $info[0], $info[1]);
            imagecopymerge($this->img, $src, $x, $y, 0, 0, $info[0], $info[1], $alpha);

            //销毁零时图片资源
            imagedestroy($src);
        } while (!empty($this->gif) && $this->gifNext());

        //销毁水印资源
        imagedestroy($water);
    }

    /**
     * 图像添加文字
     * @param $text  添加的文字
     * @param $font  字体路径
     * @param $size  字号
     * @param string $color 文字颜色
     * @param int $locate 文字写入位置
     * @param int $offset 文字相对当前位置的偏移量
     * @param int $angle 文字倾斜角度
     * @throws Exception
     */
    public function text($text, $font, $size, $color = '#00000000', $locate = Image::IMAGE_WATER_SOUTHEAST, $offset = 0, $angle = 0)
    {
        //资源检测
        if (empty($this->img))
            throw new Exception ('没有可以被写入文字的图像资源');
        if (!is_file($font))
            throw new Exception ("不存在的字体文件：{$font}");

        //获取文字信息
        $info = imagettfbbox($size, $angle, $font, $text);
        $minx = min($info[0], $info[2], $info[4], $info[6]);
        $maxx = max($info[0], $info[2], $info[4], $info[6]);
        $miny = min($info[1], $info[3], $info[5], $info[7]);
        $maxy = max($info[1], $info[3], $info[5], $info[7]);

        /* 计算文字初始坐标和尺寸 */
        $x = $minx;
        $y = abs($miny);
        $w = $maxx - $minx;
        $h = $maxy - $miny;

        /* 设定文字位置 */
        switch ($locate) {
            /* 右下角文字 */
            case Image::IMAGE_WATER_SOUTHEAST:
                $x += $this->info['width'] - $w;
                $y += $this->info['height'] - $h;
                break;

            /* 左下角文字 */
            case Image::IMAGE_WATER_SOUTHWEST:
                $y += $this->info['height'] - $h;
                break;

            /* 左上角文字 */
            case Image::IMAGE_WATER_NORTHWEST:
                // 起始坐标即为左上角坐标，无需调整
                break;

            /* 右上角文字 */
            case Image::IMAGE_WATER_NORTHEAST:
                $x += $this->info['width'] - $w;
                break;

            /* 居中文字 */
            case Image::IMAGE_WATER_CENTER:
                $x += ($this->info['width'] - $w) / 2;
                $y += ($this->info['height'] - $h) / 2;
                break;

            /* 下居中文字 */
            case Image::IMAGE_WATER_SOUTH:
                $x += ($this->info['width'] - $w) / 2;
                $y += $this->info['height'] - $h;
                break;

            /* 右居中文字 */
            case Image::IMAGE_WATER_EAST:
                $x += $this->info['width'] - $w;
                $y += ($this->info['height'] - $h) / 2;
                break;

            /* 上居中文字 */
            case Image::IMAGE_WATER_NORTH:
                $x += ($this->info['width'] - $w) / 2;
                break;

            /* 左居中文字 */
            case Image::IMAGE_WATER_WEST:
                $y += ($this->info['height'] - $h) / 2;
                break;

            default:
                /* 自定义文字坐标 */
                if (is_array($locate)) {
                    list($posx, $posy) = $locate;
                    $x += $posx;
                    $y += $posy;
                } else {
                    throw new Exception ('不支持的文字位置类型');
                }
        }

        /* 设置偏移量 */
        if (is_array($offset)) {
            $offset = array_map('intval', $offset);
            list($ox, $oy) = $offset;
        } else {
            $offset = intval($offset);
            $ox = $oy = $offset;
        }

        /* 设置颜色 */
        if (is_string($color) && 0 === strpos($color, '#')) {
            $color = str_split(substr($color, 1), 2);
            $color = array_map('hexdec', $color);
            if (empty($color[3]) || $color[3] > 127) {
                $color[3] = 0;
            }
        } elseif (!is_array($color)) {
            throw new Exception ('错误的颜色值');
        }

        do {
            /* 写入文字 */
            $col = imagecolorallocatealpha($this->img, $color[0], $color[1], $color[2], $color[3]);
            imagettftext($this->img, $size, $angle, $x + $ox, $y + $oy, $col, $font, $text);
        } while (!empty($this->gif) && $this->gifNext());
    }

    /* 切换到GIF的下一帧并保存当前帧，内部使用 */
    private function gifNext()
    {
        ob_start();
        ob_implicit_flush(0);
        imagegif($this->img);
        $img = ob_get_clean();

        $this->gif->image($img);
        $next = $this->gif->nextImage();

        if ($next) {
            $this->img = imagecreatefromstring($next);
            return $next;
        } else {
            $this->img = imagecreatefromstring($this->gif->image());
            return false;
        }
    }

    /**
     * 析构方法，用于销毁图像资源
     */
    public function __destruct()
    {
        empty($this->img) || imagedestroy($this->img);
    }
}