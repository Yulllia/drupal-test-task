<?php
namespace Drupal\feature_component\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\node\Entity\Node;
use Drupal\node\NodeInterface;
use Drupal\Core\Url;

class FeatureComponentController extends ControllerBase {

  private function process_paragraph($paragraph) {
    $block_title = '';
    $illustration_uri = '';
    $button_link = [];
    $optional_title = '';
    $secondary_title = '';
    $secondary_link = [];
    $bodyBackground = '';
  
    if ($paragraph->hasField('feature_main_title')) {
      $block_title = $paragraph->get('feature_main_title')->value;
    }
    if ($paragraph->hasField('feature_primary_cta')) {
      $link_field = $paragraph->get('feature_primary_cta')->first()->getValue(); 
      $button_link['url'] = Url::fromUri($link_field['uri'])->toString();
      $button_link['title'] = $link_field['title']; 
    }
    if ($paragraph->hasField('feature_title')) {
      $optional_title = $paragraph->get('feature_title')->value;
    }
    if ($paragraph->hasField('feature_secondary_cta_copy')) {
      $secondary_title = $paragraph->get('feature_secondary_cta_copy')->value;
    }
    if ($paragraph->hasField('feature_secondary_cta_link')) {
      $link_data = $paragraph->get('feature_secondary_cta_link')->first()->getValue();    
      $secondary_link['url'] = Url::fromUri($link_data['uri'])->toString();
      $secondary_link['title'] = $link_data['title']; 
    }

    if ($paragraph->hasField('illustration') && $paragraph->get('illustration')->entity) {
      $illustration_entity = $paragraph->get('illustration')->entity;
      $illustration_uri = \Drupal::service('file_url_generator')->generateAbsoluteString($illustration_entity->getFileUri());
    }
    if ($paragraph->hasField('background_color_style')) {
      $bgColor = $paragraph->get('background_color_style')->value;
      $bgColor = trim(strtolower($bgColor));

      if ($bgColor == 'light') {
        $bodyBackground = '#FFFFFF';
      }
      else if ($bgColor == 'grey') {
        $bodyBackground = '#F9F8F5';
      }
    }

    return [
      'blockTitle' => $block_title,
      'illustration' => $illustration_uri,
      'background' =>  $bodyBackground,
      'link' => $button_link,
      'optional_title' => $optional_title,
      'secondary_title' => $secondary_title,
      'secondary_link' => $secondary_link
    ];
  }


  public function render($animal) {
    $nodes = \Drupal::entityTypeManager()
      ->getStorage('node')
      ->loadByProperties(['type' => 'page']);

    $features = [];
    $bodyBackground = [];
    $filtered_nodes = [];

    if (empty($nodes)) {
      return [
        '#markup' => 'No content available.', 
      ];
    }

    foreach ($nodes as $node) {
      if ($node->hasField('field_paragraph') && $node->hasField('field_nested_paragraph')) {
        $all_paragraphs = [];
        $paragraphs_field = $node->get('field_paragraph')->referencedEntities();

        foreach ($paragraphs_field as $paragraph) {
          if ($paragraph->hasField('field_category')) {  
            $category = $paragraph->get('field_category')->entity;  
            
            if ($category && strtolower($category->getName()) === $animal) {  
              $filtered_nodes[] = $node;  
              break;  
            }
          }
        }
      }
    }
        foreach ($filtered_nodes as $node) {
          if ($node->hasField('field_paragraph')) {
            $paragraphs = $node->get('field_paragraph')->referencedEntities();
            $paragraphs_field_nested = $node->get('field_nested_paragraph')->referencedEntities();
        
            $all_paragraphs = array_merge($paragraphs, $paragraphs_field_nested);
            foreach ($all_paragraphs as $paragraph) {
              $features[] = $this->process_paragraph($paragraph);
            }
            foreach ($paragraphs_field as $main_paragraph) {
              $bgColor = $this->process_paragraph($main_paragraph)['background'];
              $bodyBackground[$node->id()] = $bgColor; 
            }
      }

    return [
      '#theme' => 'page--feature',  
      '#node' => $node,  
      '#paragraphs' => $features,  
      '#attached' => [
        'library' => ['feature_component/feature_style'], 
        'drupalSettings' => [
          'feature_component' => [
            'bodyBackground' => $bodyBackground[$node->id()]
          ],
        ],
      ],
    ];
  }
  }
  }
