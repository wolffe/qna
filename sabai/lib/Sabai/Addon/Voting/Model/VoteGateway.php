<?php
class Sabai_Addon_Voting_Model_VoteGateway extends Sabai_Addon_Voting_Model_Base_VoteGateway
{
    public function getResults($entityType, $entityId, $tag)
    {      
        $sql = sprintf(
            'SELECT vote_name, COUNT(*) AS cnt, SUM(vote_value) AS sm, MAX(vote_created) AS mx FROM %svoting_vote WHERE vote_entity_type = %s AND vote_entity_id = %d AND vote_tag = %s GROUP BY vote_name',
             $this->_db->getResourcePrefix(),
             $this->_db->escapeString($entityType),
             $entityId,
             implode(',', array_map(array($this->_db, 'escapeString'), (array)$tag))
        );
        $rs = $this->_db->query($sql);
        $ret = array();
        foreach ($rs as $row) {
            $ret[$row['vote_name']] = array('count' => (int)$row['cnt'], 'sum' => $row['sm'], 'last_voted_at' => $row['mx']);
        }
        
        return $ret;
    }
    
    public function getVotes($entityType, array $entityIds, $userId, array $tags = null)
    {
        $sql = sprintf(
            'SELECT vote_tag, vote_entity_id, vote_value FROM %svoting_vote WHERE vote_entity_type = %s AND vote_entity_id IN (%s) AND vote_user_id = %d %s %s',
            $this->_db->getResourcePrefix(),
            $this->_db->escapeString($entityType),
            implode(',', array_map('intval', $entityIds)),
            $userId,
            isset($tags) ? sprintf('AND vote_tag IN (%s)', implode(',', array_map(array($this->_db, 'escapeString'), $tags))) : '',
            empty($userId) ? sprintf('AND vote_ip = %s', $this->_db->escapeString(md5($this->_getIp()))) : ''
        );
        $rs = $this->_db->query($sql);
        $ret = array();
        foreach ($rs as $row) {
            $ret[$row['vote_tag']][$row['vote_entity_id']] = $row['vote_value'];
        }
        return $ret;
    }
    
    public function getRatingSummary($bundleId, $entityId)
    {
        $sql = sprintf("
            SELECT CASE WHEN vote_value = 5.00 THEN 50
                WHEN vote_value >= 4.50 AND vote_value <= 5.00 THEN 45
                WHEN vote_value >= 4.00 AND vote_value < 4.50 THEN 40
                WHEN vote_value >= 3.50 AND vote_value < 4.00 THEN 35
                WHEN vote_value >= 3.00 AND vote_value < 3.50 THEN 30
                WHEN vote_value >= 2.50 AND vote_value < 3.00 THEN 25
                WHEN vote_value >= 2.00 AND vote_value < 2.50 THEN 20
                WHEN vote_value >= 1.50 AND vote_value < 2.00 THEN 15
                WHEN vote_value >= 1.00 AND vote_value < 1.50 THEN 10
                WHEN vote_value >= 0.50 AND vote_value < 1.00 THEN 5
                WHEN vote_value >= 0.00 AND vote_value < 0.50 THEN 0
              ELSE NULL END AS rating,
              COUNT(*) AS cnt
            FROM %svoting_vote
            WHERE vote_bundle_id = %d AND vote_entity_id = %d AND vote_tag = 'rating' AND vote_name = ''
            GROUP BY rating",
            $this->_db->getResourcePrefix(),
            $bundleId,
            $entityId
        );
        $rs = $this->_db->query($sql);
        $ret = array();
        foreach ($rs as $row) {
            $ret[$row['rating']] = (int)$row['cnt'];
        }
        return $ret;
    }
    
    private function _getIp()
    {
        foreach (array('HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'REMOTE_ADDR') as $key) {
            if (!empty($_SERVER[$key])) {
                return $_SERVER[$key];
            }
        }
        return '';
    }
}