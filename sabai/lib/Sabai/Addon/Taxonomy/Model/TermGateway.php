<?php
class Sabai_Addon_Taxonomy_Model_TermGateway extends Sabai_Addon_Taxonomy_Model_Base_TermGateway
{
    public function fetchByBundle($bundleName, $limit = 0, $offset = 0, $depth = 0)
    {
        $sql = sprintf(
            'SELECT tr.tree_descendant, GROUP_CONCAT(t.term_name ORDER BY tr.tree_path_length DESC) as ancestors
               FROM %1$staxonomy_term_tree tr
               LEFT JOIN %1$staxonomy_term t ON tr.tree_ancestor = t.term_id
               WHERE t.term_entity_bundle_name = %2$s
               GROUP BY (tr.tree_descendant)
               ORDER BY ancestors',
            $this->_db->getResourcePrefix(),
            $this->_db->escapeString($bundleName)
        );
        $rs = $this->_db->query($sql, $limit, $offset);
        $ret = array();
        foreach ($rs as $row) {
            $_depth = count(explode(',', $row['ancestors']));
            if ($depth && $_depth > $depth) {
                continue;
            }
            $ret[$row['tree_descendant']] = $_depth - 1;
        }
        return $ret;
    }
        
    public function slugExists($bundle, $slug, $termId = null)
    {
        $sql = sprintf(
            'SELECT COUNT(*) FROM %staxonomy_term WHERE term_entity_bundle_name = %s AND term_name = %s %s',
            $this->_db->getResourcePrefix(),
            $this->_db->escapeString($bundle->name),
            $this->_db->escapeString($slug),
            !isset($termId) ? '' : 'AND term_id != ' . intval($termId)
        );
        return $this->_db->query($sql)->fetchSingle() > 0;
    }
    
    public function getContentCount($bundleType, array $termIds)
    {
        $sql = sprintf(
            'SELECT t1.tree_ancestor, post.post_entity_bundle_name, post.post_entity_bundle_type, COUNT(DISTINCT t2.entity_id) as cnt
               FROM %1$staxonomy_term_tree t1
               INNER JOIN %1$sentity_field_%3$s t2 ON t2.value = t1.tree_descendant
               INNER JOIN %1$scontent_post post ON post.post_id = t2.entity_id
               WHERE t1.tree_ancestor IN (%2$s) AND post.post_status = %4$s
               GROUP BY t1.tree_ancestor, post.post_entity_bundle_name',
             $this->_db->getResourcePrefix(),
             implode(',', $termIds),
             $bundleType,
             $this->_db->escapeString('published')
        );
        $rs = $this->_db->query($sql);
        $ret = array();
        foreach ($rs as $row) {
            $ret[$row['tree_ancestor']][$row['post_entity_bundle_type']] = $row['cnt'];
        }
        return $ret;     
    }
    
    protected function _getDeleteByCriteriaQuery($criteriaStr)
    {
        return sprintf(
            'DELETE taxonomy_term, taxonomy_term_tree FROM %1$staxonomy_term taxonomy_term
             LEFT JOIN %1$staxonomy_term_tree taxonomy_term_tree ON taxonomy_term_tree.tree_descendant = taxonomy_term.term_id
             WHERE %2$s', $this->_db->getResourcePrefix(), $criteriaStr);
    }
    
    public function getMaxDepth($bundleName)
    {
        $sql = sprintf(
            'SELECT MAX(tree_path_length) FROM %1$staxonomy_term_tree',
             $this->_db->getResourcePrefix()
        );
        return $this->_db->query($sql)->fetchSingle();  
    }
}