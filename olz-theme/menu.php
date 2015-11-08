<?php
$basic_menu = array(
  array('text' => 'Startseite', 'href' => get_permalink(get_page_by_path('startseite'))),
  array(),
  array('text' => 'Termine', 'href' => get_post_type_archive_link('termine')),
  array(),
  array('text' => 'Berichte', 'href' => get_post_type_archive_link('berichte')),
  array('text' => 'Galerien', 'href' => get_post_type_archive_link('galerie')),
  array('text' => 'Forum', 'href' => get_post_type_archive_link('forum')),
  array(),
  array('text' => 'Karten', 'href' => get_post_type_archive_link('karten')),
  array('text' => 'Über uns', 'href' => get_permalink(get_page_by_path('ueber-uns'))),
  array(),
  array('text' => 'Admin', 'href' => get_admin_url(), 'class' => 'small'),
);
echo '<script>var basicMenu = '.json_encode($basic_menu).'</script>';
?>
<div class="menu-list above"><div><div id="menu-list-above"></div></div></div>
<div id="menu-bar-container"><div id="menu-bar">
  <div id="menu-stripes">
    <div style="position:absolute; top:50%; margin-top:-55px; left:-120px; width:300px; height:0px; -moz-transform:rotate(-65deg); -webkit-transform:rotate(-65deg); transform:rotate(-65deg); border-top:10px solid rgb(255,255,0); border-bottom:10px solid rgb(0,0,0);">&nbsp;</div>
    <div style="position:absolute; top:50%; margin-top:-55px; right:-50px; width:400px; height:0px; -moz-transform:rotate(25deg); -webkit-transform:rotate(25deg); transform:rotate(25deg); border-top:10px solid rgb(255,255,0); border-bottom:10px solid rgb(0,0,0);">&nbsp;</div>
  </div>
  <div id="menu-scroll"><div id="menu-content">
    <div id="logo-box"></div>
    <div class="bild_der_woche">
<?php
      $wq = new WP_Query(array('post_type'=>'bild_der_woche', 'posts_per_page'=>10, 'paged'=>false));
      if ($wq->have_posts()) {
        $show = true;
        while ($wq->have_posts()) {
          $wq->the_post();
          if ($show) {
?>
      <a href="<?php echo wp_get_attachment_url(get_post_thumbnail_id($post->ID)); ?>" class="swipebox" rel="bild_der_woche" title="<?php echo esc_attr(get_the_title()); ?>"><?php the_post_thumbnail(array(512, 512)); ?></a>
      <div style="position:absolute; z-index:11; bottom:0px; width:100%; border-bottom-left-radius:5px; border-bottom-right-radius:5px; background-color:rgba(0,0,0,0.5); color:rgb(255,255,255); text-align:center;">
        <?php the_title(); echo $edithtml; ?>
      </div>
<?php
          } else if (wp_get_attachment_url(get_post_thumbnail_id($post->ID))) {
?>
      <a href="<?php echo wp_get_attachment_url(get_post_thumbnail_id($post->ID)); ?>" style="position:absolute; visibility:hidden;" class="swipebox" rel="bild_der_woche" title="<?php echo esc_attr(get_the_title()); ?>"></a>
<?php
          }
          $show = false;
        }
      } else {
        echo "Kein Bild der Woche verfügbar";
      }
      wp_reset_postdata();
?>
    </div>
    <div style="width:300px; height:180px;" class="mainbg-box"></div>
    <div style="width:80px; text-align:center; padding:5px 0px;" class="mainbg-box">
      <div style="font-weight:bold; font-size:12px;">JOM-Zähler</div>
      <div style="font-weight:bold; font-size:28px;">216</div>
      <div style="font-size:20px;">&Oslash;19,6</div>
    </div>
    <div style="width:300px; height:180px;" class="mainbg-box"></div>
  </div></div>
  <div id="menu-button"></div>
</div></div>
<div class="menu-list below"><div><div id="menu-list-below"></div></div></div>
