<?php

class ElectNext {
  private $version = '0.1';
  private $utils;

  public function __construct($utils) {
    $this->utils = $utils;
  }

  public function getVersion() {
    return $this->version;
  }

  public function install() {
    try {
      // installation code here
      //return $status;
    }

    catch (Exception $e) {
      return $this->render_exception_message($e);
    }
  }

  public function run() {
    add_action('add_meta_boxes', array($this, 'init_meta_box'));
    add_action('save_post', array($this, 'save_meta_box_data'));
    return true;
  }

  public function init_meta_box() {
    add_meta_box('electnext', 'ElectNext', array($this, 'render_meta_box'), 'post', 'normal', 'high');
  }

  public function render_meta_box($post) {
    $meta_pols = get_post_meta($post->ID, 'electnext_pols', true);
    $pols = empty($meta_pols) ? array() : $meta_pols;
    wp_nonce_field('electnext_meta_box_nonce', 'electnext_meta_box_nonce');
    ?>

    <script async src="https://electnext.dev/api/v1/info_widget.js"></script>

    <script>
      jQuery(document).ready(function($) {
        // for later
        //$('#electnext_add_pol').click(function(ev) {
        //  $('<p><label for="electnext_pol[][id]">ID:</label> <input type="text" name="electnext_pol[][id]"> <label for="electnext_pol[][name]">Name:</label> <input type="text" name="electnext_pol[][name]"></p>').appendTo(electnext_pols);
        //  ev.preventDefault();
        //});

        // scan the post content for politician names, and add ones we find to the meta box
        $('#electnext-scan-btn').on('click', function(ev) {
          ev.preventDefault();
          var content = tinyMCE.get('content').getContent().replace(/(<([^>]+)>)/ig,"");
          var possibles = ElectNext.scan_string(content);
          ElectNext.search_candidates(possibles, function(data) {
            $.each(data, function(idx, el) {
              if (!$('#electnext-pol-id-' + el.id).length) {
                $('#electnext-pols ul').append(
                  '<li class="electnext-pol" id="electnext-pol-id-' + el.id + '">'
                    + '<strong>' + el.name + '</strong> - '
                    + '<span>' + el.title + '</span>'
                    + '<i style="display:none;">' + el.id + '</i>'
                    + ' [ <a href="#" class="electnext-pol-remove">x</a> ]</li>'
                );
              }
            })
          });
        });

        // remove requested names
        $('#electnext-pols ul').on('click', '.electnext-pol-remove', function(ev) {
          ev.preventDefault();
          $(this).parent().remove();
        });

        // save the final set of names when the post is saved
        $('#post').submit(function() {
          for (var i = 0; i < $('.electnext-pol').length; i++) {
            $('#post').append(
              '<input type="hidden"'
                + ' name="electnext_pols_meta[' + i + '][id]"'
                + ' value="' + $('.electnext-pol:eq(' + i + ') i').text() + '">'
              + '<input type="hidden"'
                + ' name="electnext_pols_meta[' + i + '][name]"'
                + ' value="' + $('.electnext-pol:eq(' + i + ') strong').text() + '">'
                + '<input type="hidden"'
                + ' name="electnext_pols_meta[' + i + '][title]"'
                + ' value="' + $('.electnext-pol:eq(' + i + ') span').text() + '">'
            );
          }
        });
      });

    </script>
    <div id="electnext-pols">
        <?php

        /* for later
       <p><a href="#" id="electnext_add_pol">Add another</a></p>
        */
        ?>

      <ul>
      <?php
        if (!empty($pols)) {
          for ($i=0; $i < count($pols); ++$i)  {
            echo "<li class='electnext-pol' id='electnext-pol-id-{$pols[$i]['id']}'>"
              . "<strong>{$pols[$i]['name']}</strong> - "
              . "<span>{$pols[$i]['title']}</span>"
              . "<i style='display:none;'>{$pols[$i]['id']}</i>"
              . " [ <a href='#' class='electnext-pol-remove'>x</a> ]</li>";
          }
        }
      ?>
      </ul>

      <p><a class="button" href="#" id="electnext-scan-btn">Scan post</a></p>
      <div class="clear"></div>
    </div>
    <?php
  }

  public function save_meta_box_data($post_id) {
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
    if (!isset( $_POST['electnext_meta_box_nonce']) || !wp_verify_nonce($_POST['electnext_meta_box_nonce'], 'electnext_meta_box_nonce')) return;
    if (!current_user_can('edit_post')) return;

    $pols = $this->utils->array_map_recursive('sanitize_text_field', $_POST['electnext_pols_meta']);
    update_post_meta($post_id, 'electnext_pols', $pols);
  }

  public function render_exception_message($e) {
    return '<p><strong>'
      . __('ElectNext plugin error', 'electnext')
      . ':</strong></p><pre>'
      . $e->getMessage()
      . '</pre>';
  }
}
