<?php
namespace Drupal\az_event\Plugin\views\pager;
use Drupal\views\Plugin\views\pager\SqlBase;
use Drupal\views\Plugin\views\filter\Date;
use Drupal\Core\Form\FormStateInterface;
use Drupal\views\Views;
use Drupal\views\Plugin\views\pager\PagerPluginBase;

/**
 *
 * @ViewsPager(
 *   id = "datepager",
 *   title = @Translation("Date Pager"),
 *   short_title = @Translation("Date Pager"),
 *   help = @Translation("Pager that is based on date. Next and Prev take you to the appropriate event date."),
 *   theme = "views_view_datepager",
 * )
 */
class DatePager extends PagerPluginBase {
    public function buildOptionsForm(&$form, FormStateInterface $form_state) {
        parent::buildOptionsForm($form, $form_state);
        // You can add form elements to the pager options here.
        // $arguments = $this->view->getArguments();
        // dpm($this->view);
    }   

    public function render($input) {
        $output = parent::render($input);
        $args = $this->view->exposed_data['field_az_event_date_value_az_calendar'];
        $pagerText = '';
        $timeScale = 'day';
        if($args['min'] !== 'today'){
            $minDate = $date = new \DateTime($args['min']);
            $maxDate = $date = new \DateTime($args['max']);
            $timeGap = $minDate->diff($maxDate);
            $dateString = $args['min'];
            $formattedDate = '';
            $date = \DateTime::createFromFormat('Y-m-d', $dateString);
            if ($date !== false) {
                $formattedDate = $date->format('l, F j, Y');
            } else {
                echo "Invalid date string: $dateString";
            }
            if($args['min'] === $args['max']){
                $pagerText = $formattedDate;
            }
            else if($timeGap->days <= 6){
                $pagerText = 'Week of '.$formattedDate;
                $timeScale = 'week';
            }else if($timeGap->days > 6){
                $monthFormat = $date->format('F Y');
                $pagerText = 'Month of '.$monthFormat;
                $timeScale = 'month';
            }
            $output = [
                'pager_wrapper' => [
                    '#type' => 'html_tag',
                    '#tag' => 'div',
                    '#attributes' => ['class' => ['pager__wrapper row align-items-center justify-content-between']],
                    'prev' => [
                        '#type' => 'html_tag',
                        '#tag' => 'button',
                        '#value' => $this->t('Prev'),
                        '#attributes' => ['class' => ['pager__button--prev btn btn-red']],
                    ],
                    'date' => [
                        '#type' => 'html_tag',
                        '#tag' => 'h2',
                        '#value' => $pagerText,
                    ],
                    'next' => [
                        '#type' => 'html_tag',
                        '#tag' => 'button',
                        '#value' => $this->t('Next'),
                        '#attributes' => ['class' => ['pager__button--next btn btn-red']],
                    ],
                ],
                '#attached' => [
                    'library' => ['az_event/az_pager'],
                    'drupalSettings' => [
                        'azPager' => [
                            'timeScale' => $timeScale,
                            'currentDate' => $date,
                        ],
                    ],
                ],
            ];
        }else{
            $output = [];
        }
    return $output;
    }
}
