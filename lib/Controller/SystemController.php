<?php
/**
 * @copyright Copyright (c) 2017 Vinzenz Rosenkranz <vinzenz.rosenkranz@gmail.com>
 *
 * @author René Gieling <github@dartcafe.de>
 *
 * @license GNU AGPL version 3 or any later version
 *
 *  This program is free software: you can redistribute it and/or modify
 *  it under the terms of the GNU Affero General Public License as
 *  published by the Free Software Foundation, either version 3 of the
 *  License, or (at your option) any later version.
 *
 *  This program is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU Affero General Public License for more details.
 *
 *  You should have received a copy of the GNU Affero General Public License
 *  along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

namespace OCA\Polls\Controller;

use OCP\AppFramework\Controller;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\DataResponse;

use OCP\IGroupManager;
use OCP\IUser;
use OCP\IUserManager;
use OCP\IConfig;
use OCP\IRequest;
use OCA\Polls\Db\Share;
use OCA\Polls\Db\ShareMapper;
use OCA\Polls\Db\Vote;
use OCA\Polls\Db\VoteMapper;

class SystemController extends Controller {

	/** @var string */
	private $userId;

	/** @var IConfig */
	private $systemConfig;

	/** @var IGroupManager */
	private $groupManager;

	/** @var IUserManager */
	private $userManager;

	/** @var VoteMapper */
	private $voteMapper;

	/** @var ShareMapper */
	private $shareMapper;

	/**
	 * SystemController constructor.
	 * @param string $appName
	 * @param $userId
	 * @param IRequest $request
	 * @param IConfig $systemConfig
	 * @param IGroupManager $groupManager
	 * @param IUserManager $userManager
	 * @param VoteMapper $voteMapper
	 * @param ShareMapper $shareMapper
	 */
	public function __construct(
		string $appName,
		$userId,
		IRequest $request,
		IConfig $systemConfig,
		IGroupManager $groupManager,
		IUserManager $userManager,
		VoteMapper $voteMapper,
		ShareMapper $shareMapper
	) {
		parent::__construct($appName, $request);
		$this->voteMapper = $voteMapper;
		$this->shareMapper = $shareMapper;
		$this->userId = $userId;
		$this->systemConfig = $systemConfig;
		$this->groupManager = $groupManager;
		$this->userManager = $userManager;
	}

	/**
	 * Validate string as email address
	 * @NoAdminRequired
	 * @param string $query
	 * @return bool
	 */
	 private function isValidEmail($email) {
		 return (!preg_match('/^(([^<>()\[\]\\.,;:\s@"]+(\.[^<>()\[\]\\.,;:\s@"]+)*)|(".+"))@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\])|(([a-zA-Z\-0-9]+\.)+[a-zA-Z]{2,}))$/', $email)) ? false : true;
	 }

	/**
	 * Get a list of NC users, groups and contacts
	 * @NoAdminRequired
	 * @PublicPage
	 * @NoCSRFRequired
	 * @param string $query
	 * @param bool $getGroups - search in groups
	 * @param bool $getUsers - search in site users
	 * @param bool $getContacts - search in contacs
	 * @param array $skipGroups - group names to skip in return array
	 * @param array $skipUsers - user names to skip in return array
	 * @return DataResponse
	 */
	public function getSiteUsersAndGroups($query = '', $getGroups = true, $getUsers = true, $getContacts = true, $getMail = false, $skipGroups = array(), $skipUsers = array()) {
		$list = array();

		if ($getMail && $this->isValidEmail($query)) {
			$list[] = [
				'id' => '',
				'user' => '',
				'organisation' => '',
				'displayName' => '',
				'emailAddress' => $query,
				'desc' => $query,
				'type' => 'email',
				'icon' => 'icon-mail',
				'avatarURL' => '',
				'avatar' => '',
				'lastLogin' => '',
				'cloudId' => ''

			];
		}

		if ($getGroups) {
			foreach ($this->groupManager->search($query) as $group) {
				if (!in_array($group->getGID(), $skipGroups)) {
					$list[] = [
						'id' => $group->getGID(),
						'user' => $group->getGID(),
						'organisation' => '',
						'displayName' => $group->getGID(),
						'emailAddress' => '',
						'desc' => 'Group',
						'type' => 'group',
						'icon' => 'icon-group',
						'avatarURL' => '',
						'avatar' => '',
						'lastLogin' => '',
						'cloudId' => ''

					];
				}
			}
		}

		if ($getUsers) {
			$users = $this->userManager->searchDisplayName($query);
			foreach ($users as $user) {
				if (!in_array($user->getUID(), $skipUsers) && $user->isEnabled()) {
					$list[] = [
						'id' => $user->getUID(),
						'user' => $user->getUID(),
						'displayName' => $user->getDisplayName(),
						'organisation' => '',
						'emailAddress' => $user->getEMailAddress(),
						'desc' => 'User',
						'type' => 'user',
						'icon' => 'icon-user',
						'avatarURL' => '',
						'avatar' => '',
						'lastLogin' => $user->getLastLogin(),
						'cloudId' => $user->getCloudId()
					];
				}
			}
		}

		if ($getContacts && \OC::$server->getContactsManager()->isEnabled()) {

			foreach (\OC::$server->getContactsManager()->search($query, array('FN', 'EMAIL', 'ORG', 'CATEGORIES')) as $contact) {
				if (!array_key_exists('isLocalSystemBook', $contact) && array_key_exists('EMAIL', $contact)) {

					$emailAdresses = $contact['EMAIL'];

					if (!is_array($emailAdresses)) {
						$emailAdresses = array($emailAdresses);
					} else {
						// take the first eMail address for now
						$emailAdresses = array($emailAdresses[0]);
					}

					foreach ($emailAdresses as $emailAddress) {
						$list[] = [
							'id' => $contact['UID'],
							'user' => $contact['FN'],
							'displayName' => $contact['FN'],
							'organisation' => isset($contact['ORG']) ? $contact['ORG'] : '',
							'emailAddress' => $emailAddress,
							'desc' => 'Contact',
							'type' => 'contact',
							'icon' => 'icon-mail',
							'avatarURL' => '',
							'avatar' => '',
							'lastLogin' => '',
							'cloudId' => '',
						];
					}

				}
			}
		}

		return new DataResponse([
			'siteusers' => $list
		], Http::STATUS_OK);
	}

	/**
	 * Validate it the user name is reservrd
	 * return false, if this username already exists as a user or as
	 * a participant of the poll
	 * @NoCSRFRequired
	 * @NoAdminRequired
	 * @PublicPage
	 * @return DataResponse
	 */
	public function validatePublicUsername($pollId, $userName, $token) {

		// return forbidden, if $pollId does not match the share's pollId, force int compare
		if (intval($this->shareMapper->findByToken($token)->getPollId()) !== intVal($pollId)) {
			return new DataResponse(['result' => false, 'error' => 'wrong token'], Http::STATUS_FORBIDDEN);
		}

		// return forbidden, if the length of the userame is lower than 3 characters
		if (strlen(trim($userName)) < 3) {
			return new DataResponse(['result' => false, 'error' => 'userName too short'], Http::STATUS_FORBIDDEN);
		}

		$list = array();

		// get all groups
		$groups = $this->groupManager->search('');
		foreach ($groups as $group) {
			$list[] = [
				'id' => $group->getGID(),
				'user' => $group->getGID(),
				'type' => 'group',
				'displayName' => $group->getGID(),
			];
		}

		// get all users
		$users = $this->userManager->searchDisplayName('');
		foreach ($users as $user) {
			$list[] = [
				'id' => $user->getUID(),
				'user' => $user->getUID(),
				'type' => 'user',
				'displayName' => $user->getDisplayName(),
			];
		}

		// get all participants
		$votes = $this->voteMapper->findParticipantsByPoll($pollId);
		foreach ($votes as $vote) {
			if ($vote->getUserId() !== '' && $vote->getUserId() !== null) {
				$list[] = [
					'id' => $vote->getUserId(),
					'user' => $vote->getUserId(),
					'type' => 'participant',
					'displayName' => $vote->getUserId(),
				];
			}
		}

		// get all shares for this poll
		$shares = $this->shareMapper->findByPoll($pollId);
		foreach ($shares as $share) {
			if ($share->getUserId() !== '' && $share->getUserId() !== null) {
				$list[] = [
					'id' => $share->getUserId(),
					'user' => $share->getUserId(),
					'type' => 'share',
					'displayName' => $share->getUserId(),
				];
			}
		}

		// check if the username is contained inside the generated list
		// return forbidden, if list contains requested username
		foreach ($list as $element) {
			if (strtolower(trim($userName)) === strtolower(trim($element['id'])) || strtolower(trim($userName)) === strtolower(trim($element['displayName']))) {
				return new DataResponse([
					'result' => false
				], Http::STATUS_FORBIDDEN);
			}
		}

		// return OK, if username is allowed
		return new DataResponse([
			'result' => true,
			'name' => $userName
		], Http::STATUS_OK);
	}

	// public function getDisplayName() {
	// 	$this->userManager = \OC::$server->getUserManager();
	//
	// 	if (\OC::$server->getUserManager()->get($this->userId) instanceof IUser) {
	// 		return \OC::$server->getUserManager()->get($this->userId)->getDisplayName();
	// 	} else {
	// 		return $this->userId;
	// 	}
	// }
}
