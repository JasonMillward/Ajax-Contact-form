<?php
/**
 * Simple config file for jCode Lab
 *
 *
 * Released under the MIT license
 *
 * @copyright  2012 jCode
 * @category   config
 * @version    $Id$
 * @author     Jason Millward <jason@jcode.me>
 * @license    http://opensource.org/licenses/MIT
 * @package    jCode Lab
 */

require SMARTY_DIR . '/Smarty.class.php';

/**
 *
 */
class jTPL extends Smarty
{
    /**
     * [__construct description]
     * @param [type] $templateDir [description]
     */
    function __construct( $templateDir )
    {
		parent::__construct();

        $this->template_dir = $templateDir;

        $this->compile_dir  = SMARTY_WRITE . '/templates_c';
        $this->config_dir   = SMARTY_WRITE . '/configs';
        $this->cache_dir    = SMARTY_WRITE . '/cache';

        $this->assign('url',    BASEURL);
        $this->assign('dir',    BASEDIR);
        $this->assign('brand',  BRAND);
    }

}
