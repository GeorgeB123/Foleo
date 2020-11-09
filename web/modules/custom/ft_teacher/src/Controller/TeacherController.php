<?php

namespace Drupal\ft_teacher\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\user\UserInterface;

/**
 * Defines TeacherController class.
 */
class TeacherController extends ControllerBase {

  /**
   * Approve the user and add them to the group.
   *
   * @param UserInterface $user
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function approveTeacher(UserInterface $user) {
    $user->activate();
    $user->save();

    if ($user->hasField('field_school')) {
      /** @var \Drupal\group\Entity\GroupInterface $school */
      $school = $user->field_school->entity;
      $school->addMember($user);
      $school->save();
    }

    return $this->redirect('view.teachers.page_1');
  }

}
