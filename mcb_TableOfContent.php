<?php

/**
 * @see README.mb for further details
 *
 * @package Pico
 * @subpackage mcb_TableOfContent
 * @version 0.1 alpha
 * @author mcbSolutions.at <dev@mcbsolutions.at>
 */
class mcb_TableOfContent {

   // default settings
   private $depth = 3;
   private $min_headers = 3;
   private $caption = '';
   private $anchor = false;
   // internal
   private $toc = '';
   private $xpQuery;

   private function makeToc(&$content)
   {
      //get the headings
      if(preg_match_all('/<h[3-'.$this->depth.']{1,1}[^>]*>.*?<\/h[1-'.$this->depth.']>/s',$content,$headers) === false)
         return "";

      //create the toc
      $heads = implode("\n",$headers[0]);
      $heads = preg_replace('/<a.+?\/a>/','',$heads);
      $heads = preg_replace('/<h([3-6]) id="?/','<li class="toc$1"><a class="skip" href="#',$heads);
      $heads = preg_replace('/<\/h[3-6]>/','</a></li>',$heads);

      $cap = $this->caption =='' ? "" :  '<p id="toc-header">'.$this->caption.'</p>';

      return '<div id="toc"><ul><li>Inhoudstafel</li></ul>'.$cap.'<ul>'.$heads.'</ul></div>';
   }

   public function config_loaded(&$settings)
   {
      if(isset($settings['mcb_toc_depth'      ])) $this->depth       = &$settings['mcb_toc_depth'];
      if(isset($settings['mcb_toc_min_headers'])) $this->min_headers = &$settings['mcb_toc_min_headers'];
      if(isset($settings['mcb_toc_caption'    ])) $this->caption     = &$settings['mcb_toc_caption'];
      if(isset($settings['mcb_toc_anchor'     ])) $this->anchor      = &$settings['mcb_toc_anchor'];

      for ($i=1; $i <= $this->depth; $i++) {
         $this->xpQuery[] = "//h$i";
      }
      $this->xpQuery = join("|", $this->xpQuery);
   }

   public function after_parse_content(&$content)
   {
      if(trim($content)=="")
        return;
      // Workaround from cbuckley:
      // "... an alternative is to prepend the HTML with an XML encoding declaration, provided that the
      // document doesn't already contain one:
      //
      // http://stackoverflow.com/questions/8218230/php-domdocument-loadhtml-not-encoding-utf-8-correctly
      $html = @DOMDocument::loadHTML('<?xml encoding="utf-8" ?>' . $content);
      $xp = new DOMXPath($html);

      $nodes =$xp->query($this->xpQuery);

      if($nodes->length < $this->min_headers)
         return;

      // add missing id's to the h tags
      $id = 0;
      foreach($nodes as $i => $sort) {
            if (isset($sort->tagName) && $sort->tagName !== '' && $sort->getAttribute('id') === "") {
               ++$id;
                  $sort->setAttribute('id', "mcb_toc_head$id");
                  $sort->setAttribute('data-goto', "[id='mcb_toc_head$id']");
            }
      }

      $content = preg_replace(
                     array("/<(!DOCTYPE|\?xml).+?>/", "/<\/?(html|body)>/"),
                     array(                         "",                   ""),
                     $html->saveHTML()
                              );

      $this->toc = $this->makeToc($content);
   }

   public function before_render(&$twig_vars, &$twig)
   {
      $twig_vars['mcb_toc'] = $this->toc;
   }
}
