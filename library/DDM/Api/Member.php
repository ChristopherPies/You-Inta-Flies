<?php

/**
 * Provides a simple interface to work with the DDM member API
 */
class DDM_Api_Member extends DDM_Api_Nonce
{

/*********************************
** Member Related Calls
*********************************/

    /**
     * Adds a member record by an email address
     *
     * @param string $email
     * @param array $data
     * @return int $id
     */
    public function addMember($email, $data = array())
    {
        return $this->__call('addMember', array(
            'email' => $email,
            'data' => $data,
        ));
    }

    /**
     * Adds member records
     *
     * @param string $email
     * @param array $data
     * @return int $id
     */
    public function addMembers($members)
    {
        return $this->__call('addMembers', array(
            'members' => $members,
        ));
    }

    /**
     * Updates a member record by an id
     *
     * @param int $id
     * @param array $data
     * @return int $id
     */
    public function updateMember($id, $data)
    {
        return $this->__call('updateMember', array(
            'id' => $id,
            'data' => $data,
        ));
    }

    /**
     * Updates a member record by an email address
     *
     * @param string $email
     * @param array $data
     * @return int $id
     */
    public function updateMemberByEmail($email, $data)
    {
        return $this->__call('updateMemberByEmail', array(
            'email' => $email,
            'data' => $data,
        ));
    }

    /**
     * Loads a member by a member_id
     *
     * @param int $id
     * @return array
     */
    public function getMemberById($id)
    {
        return $this->__call('getMemberById', array(
            'id' => $id,
        ));
    }

    /**
     * Loads a member by a member_id
     *
     * @param int $id
     * @return array
     */
    public function getMemberBySiteIdAndSiteMemberId($site_id, $site_member_id)
    {
        return $this->__call('getMemberBySiteIdAndSiteMemberId', array(
            'site_id' => $site_id,
            'site_member_id' => $site_member_id,
        ));
    }

    /**
     * Loads a member by an email address
     *
     * @param string $email
     * @return array
     */
    public function getMemberByEmail($email)
    {
        return $this->__call('getMemberByEmail', array(
            'email' => $email,
        ));
    }

    /**
     * Get a member by username and password
     *
     * @param string $username
     * @param string $password
     * @return array|false
     */
    public function getMemberByUsernameAndPassword($username, $password)
    {
        return $this->__call('getMemberByUsernameAndPassword', array(
            'username' => $username,
            'password' => $password,
        ));
    }

    /**
     * Returns a member by a specified authToken
     *
     * @param string $authToken
     *
     * @return array|false
     */
    public function getMemberByAuthToken($authToken)
    {
        return $this->__call(
            'getMemberByAuthToken',
            array(
                'auth_token' => $authToken,
            )
        );
    }

    /**
     * Deletes the specified auth token
     *
     * @param string $authToken
     *
     * @return boolean
     */
    public function deleteAuthToken($authToken)
    {
        return $this->__call(
            'deleteAuthToken',
            array(
                'auth_token' => $authToken,
            )
        );
    }

    /**
     * Deletes all the auth tokens for a specified memberId
     *
     * @param int $memberId
     *
     * @return boolean
     */
    public function deleteAuthTokensByMemberId($memberId)
    {
        return $this->__call(
            'deleteAuthTokensByMemberId',
            array(
                'member_id' => $memberId,
            )
        );
    }

/*********************************
** Member Site Related Calls
*********************************/

    /**
     * Add a member site record for specified member_id
     *
     * @param int $member_id
     * @param int $site_id
     * @param int $site_member_id
     */
    public function addMemberSite($member_id, $site_id, $site_member_id)
    {
        return $this->__call('addMemberSite', array(
            'member_id' => $member_id,
            'site_id' => $site_id,
            'site_member_id' => $site_member_id,
        ));
    }

    /**
     * Add an array of member_sites to the specified member_id
     *
     * @param int $member_id
     * @param array $site_member_ids
     */
    public function addMemberSites($member_id, $site_member_ids)
    {
        return $this->__call('addMemberSites', array(
            'member_id' => $member_id,
            'site_member_ids' => $site_member_ids,
        ));
    }

    /**
     * Deletes a member site from a member
     *
     * @param int $member_id
     * @param int $site_id
     * @param int $site_member_id
     */
    public function deleteMemberSite($member_id, $site_id, $site_member_id)
    {
        return $this->__call('deleteMemberSite', array(
            'member_id' => $member_id,
            'site_id' => $site_id,
            'site_member_id' => $site_member_id,
        ));
    }

/*********************************
** Status Related Calls
*********************************/

    /**
     * Gets a list of statuses
     *
     * @param array $statuses
     */
    public function getStatuses()
    {
        return $this->__call('getStatuses');
    }

    /**
     * Gets a specific status by id
     *
     * @param int $id
     */
    public function getStatusById($id)
    {
        return $this->__call('getStatusById', array(
            'id' => $id,
        ));
    }

    /**
     * Gets a specific status by title
     *
     * @param string $title
     */
    public function getStatusByTitle($title)
    {
        return $this->__call('getStatusByTitle', array(
            'title' => $title,
        ));
    }
}