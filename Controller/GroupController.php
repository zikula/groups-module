<?php

/*
 * This file is part of the Zikula package.
 *
 * Copyright Zikula Foundation - http://zikula.org/
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zikula\GroupsModule\Controller;

use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Zikula\Core\Controller\AbstractController;
use Zikula\Core\Event\GenericEvent;
use Zikula\GroupsModule\Entity\GroupEntity;
use Zikula\GroupsModule\Form\Type\CreateGroupType;
use Zikula\GroupsModule\Form\Type\DeleteGroupType;
use Zikula\GroupsModule\Form\Type\EditGroupType;
use Zikula\GroupsModule\GroupEvents;
use Zikula\GroupsModule\Helper\CommonHelper;
use Zikula\ThemeModule\Engine\Annotation\Theme;

/**
 * @Route("/admin")
 *
 * Administrative controllers for the groups module
 */
class GroupController extends AbstractController
{
    /**
     * @Route("/list/{startnum}", requirements={"startnum" = "\d+"})
     * @Method("GET")
     * @Theme("admin")
     * @Template
     *
     * View a list of all groups
     *
     * @param integer $startnum
     * @return array
     * @throws AccessDeniedException Thrown if the user hasn't permissions to administer any groups
     */
    public function listAction($startnum = 0)
    {
        $itemsPerPage = $this->getVar('itemsperpage', 25);
        $groupsCommon = new CommonHelper($this->getTranslator());
        $groups = $this->get('doctrine')->getManager()->getRepository('ZikulaGroupsModule:GroupEntity')->findBy([], [], $itemsPerPage, $startnum);
        $users = $this->get('zikula_groups_module.group_application_repository')->getFilteredApplications();

        return [
            'groups' => $groups,
            'groupTypes' => $groupsCommon->gtypeLabels(),
            'states' => $groupsCommon->stateLabels(),
            'userItems' => $users,
            'defaultGroup' => $this->getVar('defaultgroup'),
            'primaryAdminGroup' => $this->getVar('primaryadmingroup', 2),
            'pager' => [
                'amountOfItems' => count($groups),
                'itemsPerPage' => $itemsPerPage
            ]
        ];
    }

    /**
     * @Route("/create")
     * @Theme("admin")
     * @Template
     *
     * Display a form to add a new group.
     *
     * @param Request $request
     * @return array|RedirectResponse
     */
    public function createAction(Request $request)
    {
        if (!$this->hasPermission('ZikulaGroupsModule::', '::', ACCESS_ADD)) {
            throw new AccessDeniedException();
        }

        $form = $this->createForm(CreateGroupType::class, new GroupEntity(), [
            'translator' => $this->get('translator.default')
        ]);

        if ($form->handleRequest($request)->isValid()) {
            if ($form->get('save')->isClicked()) {
                $groupEntity = $form->getData();
                $this->get('doctrine')->getManager()->persist($groupEntity);
                $this->get('doctrine')->getManager()->flush();
                $this->get('event_dispatcher')->dispatch(GroupEvents::GROUP_CREATE, new GenericEvent($groupEntity));
                $this->addFlash('status', $this->__('Done! Created the group.'));
            }
            if ($form->get('cancel')->isClicked()) {
                $this->addFlash('status', $this->__('Operation cancelled.'));
            }

            return $this->redirectToRoute('zikulagroupsmodule_group_list');
        }

        return [
            'form' => $form->createView()
        ];
    }

    /**
     * @Route("/edit/{gid}", requirements={"gid" = "^[1-9]\d*$"})
     * @Theme("admin")
     * @Template
     *
     * Modify a group.
     *
     * @param Request $request
     * @param GroupEntity $groupEntity
     * @return array|RedirectResponse
     */
    public function editAction(Request $request, GroupEntity $groupEntity)
    {
        if (!$this->hasPermission('ZikulaGroupsModule::', $groupEntity->getGid() . '::', ACCESS_EDIT)) {
            throw new AccessDeniedException();
        }

        $form = $this->createForm(EditGroupType::class, $groupEntity, [
            'translator' => $this->get('translator.default')
        ]);

        if ($form->handleRequest($request)->isValid()) {
            if ($form->get('save')->isClicked()) {
                $groupEntity = $form->getData();
                $this->get('doctrine')->getManager()->persist($groupEntity); // this isn't technically required
                $this->get('doctrine')->getManager()->flush();
                $this->get('event_dispatcher')->dispatch(GroupEvents::GROUP_UPDATE, new GenericEvent($groupEntity));
                $this->addFlash('status', $this->__('Done! Updated the group.'));
            }
            if ($form->get('cancel')->isClicked()) {
                $this->addFlash('status', $this->__('Operation cancelled.'));
            }

            return $this->redirectToRoute('zikulagroupsmodule_group_list');
        }

        return [
            'form' => $form->createView()
        ];
    }

    /**
     * @Route("/remove/{gid}", requirements={"gid"="\d+"})
     * @Theme("admin")
     * @Template
     *
     * Deletes a group.
     *
     * @param Request $request
     * @param GroupEntity $groupEntity
     * @return array|RedirectResponse
     */
    public function removeAction(Request $request, GroupEntity $groupEntity)
    {
        if (!$this->hasPermission('ZikulaGroupsModule::', $groupEntity->getGid() . '::', ACCESS_DELETE)) {
            throw new AccessDeniedException();
        }

        // get the user default group - we do not allow its deletion
        $defaultGroup = $this->getVar('defaultgroup', 1);
        if ($groupEntity->getGid() == $defaultGroup) {
            $this->addFlash('error', $this->__('Error! You cannot delete the default user group.'));

            return $this->redirectToRoute('zikulagroupsmodule_group_list');
        }

        // get the primary admin group - we do not allow its deletion
        $primaryAdminGroup = $this->getVar('primaryadmingroup', 2);
        if ($groupEntity->getGid() == $primaryAdminGroup) {
            $this->addFlash('error', $this->__('Error! You cannot delete the primary administration group.'));

            return $this->redirectToRoute('zikulagroupsmodule_group_list');
        }

        $form = $this->createForm(DeleteGroupType::class, $groupEntity, [
            'translator' => $this->get('translator.default')
        ]);

        if ($form->handleRequest($request)->isValid()) {
            if ($form->get('delete')->isClicked()) {
                $groupEntity = $form->getData();
                $this->get('doctrine')->getManager()->remove($groupEntity);
                $this->get('doctrine')->getManager()->flush();
                $this->get('event_dispatcher')->dispatch(GroupEvents::GROUP_DELETE, new GenericEvent($groupEntity));
                $this->addFlash('status', $this->__('Done! Group deleted.'));
            }
            if ($form->get('cancel')->isClicked()) {
                $this->addFlash('status', $this->__('Operation cancelled.'));
            }

            return $this->redirectToRoute('zikulagroupsmodule_group_list');
        }

        return [
            'form' => $form->createView(),
            'group' => $groupEntity
        ];
    }
}
