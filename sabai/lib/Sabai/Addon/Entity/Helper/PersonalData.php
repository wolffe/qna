<?php
class Sabai_Addon_Entity_Helper_PersonalData extends Sabai_Helper
{
    public function help(Sabai $application, $bundleName, array $fields, $email, $userId, $limit = 0, $offset = 0)
    {
        $exported = array();
        if (!$bundle = $application->Entity_Bundle($bundleName)) return $exported;

        foreach (array_keys($fields) as $personal_data_identifier) {
            $query = $application->Entity_Query($bundle->entitytype_name)->propertyIs('post_entity_bundle_name', $bundleName);
            if ($personal_data_identifier === 'author') {
                if (!isset($author_fields)) $author_fields = $this->authorFields($application, $bundle);

                $query->startCriteriaGroup('OR');
                foreach (array_keys($author_fields) as $field_name) {
                    $author_field = $author_fields[$field_name];
                    $application->Field_TypeImpl($author_field->getFieldType())->fieldPersonalDataQuery(
                        $query->getFieldQuery(),
                        ($property = $author_field->isPropertyField()) ? $property : $field_name,
                        $email,
                        $userId
                    );
                }
                $query->finishCriteriaGroup();
            } else {
                if ((!$identifier_field = $application->Entity_Field($bundle->name, $personal_data_identifier))
                    || (!$identifier_field_type = $application->Field_TypeImpl($identifier_field->getFieldType(), true))
                    || !$identifier_field_type instanceof Sabai_Addon_Field_IPersonalDataIdentifier
                ) continue;

                $identifier_field_type->fieldPersonalDataQuery(
                    $query->getFieldQuery(),
                    ($property = $identifier_field->isPropertyField()) ? $property : $identifier_field->getFieldName(),
                    $email,
                    $userId
                );
            }

            foreach ($query->fetch($limit, $offset) as $entity) {

                foreach ($fields[$personal_data_identifier] as $field_name) {
                    if (!$entity->getFieldValue($field_name) // no value
                        || (!$field = $application->Entity_Field($bundle->name, $field_name))
                    ) continue;

                    if (null !== $personal_data = $application->Field_TypeImpl($field->getFieldType())->fieldPersonalDataExport($field, $entity)) {
                        if (is_array($personal_data)) {
                            foreach ($personal_data as $key => $_personal_data) {
                                $exported[$entity->getId()][$field_name . '-' . $key] = array(
                                    'name' => $field->getFieldLabel() . ' - ' . $_personal_data['name'],
                                    'value' => $_personal_data['value'],
                                );
                            }
                        } else {
                            $exported[$entity->getId()][$field_name] = array(
                                'name' => $field->getFieldLabel(),
                                'value' => $personal_data,
                            );
                        }
                    }
                }
            }
            
        }

        return $exported;
    }

    public function fields(Sabai $application)
    {
        $fields = array();
        foreach ($application->Entity_Bundles() as $bundle) {
            foreach ($application->Entity_Field($bundle->name) as $field) {
                if ((!$field_type = $application->Field_TypeImpl($field->getFieldType(), true))
                    || !$field_type instanceof Sabai_Addon_Field_IPersonalData
                ) continue;

                if (null !== $is_personal_data = $field->getFieldData('_is_personal_data')) {
                    if (!$personal_data_identifier = $field->getFieldData('_personal_data_identifier')) continue;
                } else {
                    if (!$field_type instanceof Sabai_Addon_Field_IPersonalDataIdentifier) continue;

                    if ($field_type instanceof Sabai_Addon_Entity_IPersonalDataAuthorFieldType) {
                        $personal_data_identifier = 'author';
                    } else {
                        $personal_data_identifier = $field->getFieldName();
                    }
                }

                $fields[$bundle->name][$personal_data_identifier][$field->getFieldName()] = $field->getFieldName();
            }
        }
        foreach (array_keys($fields) as $bundle_name) {
            foreach (array_keys($fields[$bundle_name]) as $personal_data_identifier) {
                if ($personal_data_identifier === 'author') continue;

                if ((!$identifier_field = $application->Entity_Field($bundle_name, $personal_data_identifier))
                    || (!$identifier_field_type = $application->Field_TypeImpl($identifier_field->getFieldType(), true))
                    || !$identifier_field_type instanceof Sabai_Addon_Field_IPersonalDataIdentifier
                ) {
                    // Invalid personal data identifier field
                    unset($fields[$bundle_name][$personal_data_identifier]);
                }
            }
        }

        return $fields;
    }

    public function identifierFieldOptions(Sabai $application, Sabai_Addon_Entity_Model_Bundle $bundle)
    {
        $ret = array('author' => __('Author ID', '-sabai_plugin_name'));
        foreach ($application->Entity_Field($bundle->name) as $field_name => $field) {
            if ((!$field_type = $application->Field_TypeImpl($field->getFieldType(), true))
                || $field_type instanceof Sabai_Addon_Entity_IPersonalDataAuthorFieldType
                || !$field_type instanceof Sabai_Addon_Field_IPersonalDataIdentifier
            ) continue;

            $ret[$field_name] =__('Field - ', 'sabai') . $field->getFieldLabel();
        }
        return $ret;
    }

    public function authorFields(Sabai $application, Sabai_Addon_Entity_Model_Bundle $bundle)
    {
        $ret = array();
        foreach ($application->Entity_Field($bundle->name) as $field_name => $field) {
            if ((!$field_type = $application->Field_TypeImpl($field->getFieldType(), true))
                || (!$field_type instanceof Sabai_Addon_Entity_IPersonalDataAuthorFieldType)
            ) continue;

            $ret[$field_name] = $field;
        }
        return $ret;
    }

    public function erase(Sabai $application, $bundleName, array $fields, $email, $userId, $limit = 0, $offset = 0)
    {
        $results = array('deleted' => 0, 'retained' => 0, 'messages' => array());
        if (!$bundle = $application->Entity_Bundle($bundleName)) return $results;

        foreach (array_keys($fields) as $personal_data_identifier) {
            $query = $application->Entity_Query($bundle->entitytype_name)->propertyIs('post_entity_bundle_name', $bundleName);
            $identifier_fields = array();
            if ($personal_data_identifier === 'author') {
                if (!isset($author_fields)) $author_fields = $this->authorFields($application, $bundle);

                $query->startCriteriaGroup('OR');
                foreach (array_keys($author_fields) as $field_name) {
                    $author_field = $author_fields[$field_name];
                    $application->Field_TypeImpl($author_field->getFieldType())->fieldPersonalDataQuery(
                        $query->getFieldQuery(),
                        ($property = $author_field->isPropertyField()) ? $property : $field_name,
                        $email,
                        $userId
                    );
                    $identifier_fields[$field_name] = $author_field;
                }
                $query->finishCriteriaGroup();

            } else {
                if ((!$identifier_field = $application->Entity_Field($bundle->name, $personal_data_identifier))
                    || (!$identifier_field_type = $application->Field_TypeImpl($identifier_field->getFieldType(), true))
                    || !$identifier_field_type instanceof Sabai_Addon_Field_IPersonalDataIdentifier
                ) continue;

                $identifier_field_name = ($property = $identifier_field->isPropertyField()) ? $property : $identifier_field->getFieldName();
                $identifier_field_type->fieldPersonalDataQuery($query->getFieldQuery(), $identifier_field_name, $email, $userId);
                $identifier_fields[$identifier_field->getFieldName()] = $identifier_field;
            }
            foreach ($query->fetch($limit, $offset) as $entity) {
                $values = array();

                // Erase personal data from fields
                foreach ($fields[$personal_data_identifier] as $field_name) {
                    if (!$entity->getFieldValue($field_name) // no value
                        || (!$field = $application->Entity_Field($bundle->name, $field_name))
                    ) continue;

                    if ($result = $application->Field_TypeImpl($field->getFieldType())->fieldPersonalDataErase($field, $entity)) {
                        $values[$field_name] = $result === true ? false : $result; // delete if true
                        ++$results['deleted'];
                    } else {
                        ++$results['retained'];
                        $results['messages'][] = sprintf(
                            __('%s contains personal data but could not be erased.', 'sabai'),
                            $bundle->getLabel('singular') . ' (ID: ' . $entity->getId() . ')'
                        );
                    }
                }

                if (!empty($values)) {
                    // Anonymize identifier fields if have not already been erased
                    foreach (array_keys($identifier_fields) as $identifier_field_name) {
                        $identifier_field = $identifier_fields[$identifier_field_name];
                        if (!isset($values[$identifier_field_name])) {
                            $result = $application->Field_TypeImpl($identifier_field->getFieldType())->fieldPersonalDataAnonymize($identifier_field, $entity);
                            $values[$identifier_field_name] = $result === true ? false : $result; // delete if true
                        }
                    }
                    // Save entity
                    $application->Entity_Save($entity, $values);
                }
            }
        }

        return $results;
    }
}
