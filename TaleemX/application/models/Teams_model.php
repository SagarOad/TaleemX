<?php

if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

class Teams_model extends MY_Model
{
    protected $table = "teams";
    protected $current_session;

    public function __construct()
    {
        parent::__construct();
        $this->current_session = $this->setting_model->getCurrentSession();
    }

    public function add($data, $sections)
    {
        $this->db->trans_start();
        $this->db->trans_strict(false);
        $data['session_id'] = $this->current_session;
        $this->db->insert($this->table, $data);
        $inserted_id = $this->db->insert_id();

        if (!empty($sections)) {
            $insert_section = array();
            foreach ($sections as $section_value) {
                if ($section_value === '' || $section_value === null) {
                    continue;
                }
                $insert_section[] = array(
                    'teams_id'       => $inserted_id,
                    'cls_section_id' => (int) $section_value,
                );
            }
            if (!empty($insert_section)) {
                $this->db->insert_batch('teams_sections', $insert_section);
            }
        }

        $message   = INSERT_RECORD_CONSTANT . " On " . $this->table . " id " . $inserted_id;
        $action    = "Insert";
        $record_id = $inserted_id;
        $this->log($message, $record_id, $action);

        $this->db->trans_complete();
        if ($this->db->trans_status() === false) {
            $this->db->trans_rollback();
            return false;
        }

        return true;
    }

    public function get($id = null)
    {
        $this->db->select('teams.*,for_create.name as `create_for_name`,for_create.surname as `create_for_surname`,for_create.employee_id as `for_create_empid`,create_by.name as `create_by_name`,create_by.surname as `create_by_surname`,create_by.employee_id as `create_by_empid`')->from('teams');
        $this->db->join('staff as for_create', 'for_create.id = teams.staff_id', 'left');
        $this->db->join('staff as create_by', 'create_by.id = teams.created_id');
        if ($id != null) {
            $this->db->where('teams.id', (int) $id);
        } else {
            $this->db->order_by('teams.id');
        }
        $query = $this->db->get();
        return ($id != null) ? $query->row() : $query->result();
    }

    public function getByStaff($staff_id = null)
    {
        $this->db->select('teams.*,for_create.name as `create_for_name`,for_create.surname as `create_for_surname`,create_by.name as `create_by_name`,create_by.surname as `create_by_surname`,for_create.employee_id as `for_create_employee_id`,for_create_role.name as `for_create_role_name`,create_by_role.name as `create_by_role_name`,create_by.employee_id as `create_by_employee_id`,staff_create_by_roles.role_id')->from('teams');
        $this->db->join('staff as for_create', 'for_create.id = teams.staff_id');
        $this->db->join('staff as create_by', 'create_by.id = teams.created_id');
        $this->db->join('staff_roles', 'staff_roles.staff_id = for_create.id');
        $this->db->join('roles as `for_create_role`', 'for_create_role.id = staff_roles.role_id');
        $this->db->join('staff_roles as staff_create_by_roles', 'staff_create_by_roles.staff_id = create_by.id');
        $this->db->join('roles as `create_by_role`', 'create_by_role.id = staff_create_by_roles.role_id');
        $this->db->where('teams.session_id', $this->current_session);
        if ($staff_id != "") {
            $this->db->where('teams.staff_id', $staff_id);
        }
        $this->db->order_by('DATE(`teams`.`date`)', 'DESC');
        $this->db->order_by('teams.date', 'DESC');
        $query = $this->db->get();
        $result = $query->result();
        foreach ($result as $row) {
            $row->{'classes'} = $this->getClassSectionByTeamsID($row->id);
        }
        return $result;
    }

    public function getClassSectionByTeamsID($teams_id)
    {
        $this->db->select('teams_sections.*,class_sections.class_id,class_sections.section_id,classes.class,sections.section')->from('teams_sections');
        $this->db->join('class_sections', 'class_sections.id = teams_sections.cls_section_id');
        $this->db->join('classes', 'classes.id = class_sections.class_id');
        $this->db->join('sections', 'sections.id = class_sections.section_id');
        $this->db->where('teams_sections.teams_id', (int) $teams_id);
        $this->db->order_by('teams_sections.id');
        $query = $this->db->get();
        return $query->result();
    }

    public function getByClassSection($class_id, $section_id)
    {
        $this->db->select('teams.*,classes.class,sections.section,for_create.name as `create_for_name`,for_create.surname as `create_for_surname`,for_create.employee_id as `for_create_employee_id`,for_create_role.name as `for_create_role_name`')->from('teams_sections');
        $this->db->join('teams', 'teams.id = teams_sections.teams_id');
        $this->db->join('class_sections', 'class_sections.id = teams_sections.cls_section_id');
        $this->db->join('classes', 'classes.id = class_sections.class_id');
        $this->db->join('sections', 'sections.id = class_sections.section_id');
        $this->db->join('staff as for_create', 'for_create.id = teams.staff_id');
        $this->db->join('staff_roles', 'staff_roles.staff_id = for_create.id');
        $this->db->join('roles as `for_create_role`', 'for_create_role.id = staff_roles.role_id');
        $this->db->where('class_sections.class_id', $class_id);
        $this->db->where('class_sections.section_id', $section_id);
        $this->db->where('teams.session_id', $this->current_session);
        $this->db->order_by('DATE(`teams`.`date`)', 'DESC');
        $this->db->order_by('teams.date', 'DESC');
        $query = $this->db->get();
        return $query->result();
    }

    public function remove($id)
    {
        $this->db->trans_start();
        $this->db->trans_strict(false);
        $this->db->where('id', (int) $id);
        $this->db->delete('teams');

        $message   = DELETE_RECORD_CONSTANT . " On teams id " . $id;
        $action    = "Delete";
        $record_id = $id;
        $this->log($message, $record_id, $action);

        $this->db->trans_complete();
        if ($this->db->trans_status() === false) {
            $this->db->trans_rollback();
            return false;
        }

        return true;
    }

    public function update($id, $data)
    {
        $this->db->trans_start();
        $this->db->trans_strict(false);
        $this->db->where('id', (int) $id);
        $this->db->update("teams", $data);

        $message   = UPDATE_RECORD_CONSTANT . " On teams id " . $id;
        $action    = "Update";
        $record_id = $id;
        $this->log($message, $record_id, $action);

        $this->db->trans_complete();
        if ($this->db->trans_status() === false) {
            $this->db->trans_rollback();
            return false;
        }

        return true;
    }

    public function getByIdSession($id)
    {
        $this->db->where('id', (int) $id);
        $this->db->where('session_id', $this->current_session);
        $query = $this->db->get('teams');
        return $query->row();
    }

    public function replaceSections($teams_id, $sections)
    {
        $this->db->where('teams_id', (int) $teams_id)->delete('teams_sections');
        if (empty($sections)) {
            return;
        }

        $batch = array();
        foreach ($sections as $section_value) {
            if ($section_value === '' || $section_value === null) {
                continue;
            }
            $batch[] = array(
                'teams_id'       => (int) $teams_id,
                'cls_section_id' => (int) $section_value,
            );
        }
        if (!empty($batch)) {
            $this->db->insert_batch('teams_sections', $batch);
        }
    }

    public function get_urlByTeamsId($id)
    {
        return $this->db->select('teams.url,teams.title,teams.date,teams.duration')->from('teams')->where('teams.id', (int) $id)->get()->row_array();
    }

    public function getStudentByClassSectionID($class_section_id)
    {
        $this->db->select('student_session.id as `student_session_id`,classes.class,sections.section,students.id,students.admission_no,students.roll_no,students.firstname,students.lastname,students.mobileno,students.email,students.guardian_email,students.guardian_phone,students.app_key,students.parent_app_key')->from('students');
        $this->db->join('student_session', 'student_session.student_id = students.id');
        $this->db->join('classes', 'student_session.class_id = classes.id');
        $this->db->join('sections', 'sections.id = student_session.section_id');
        $this->db->join('users', 'users.user_id = students.id', 'left');
        $this->db->join('class_sections', 'class_sections.class_id=classes.id and class_sections.section_id=sections.id');
        $this->db->where_in('class_sections.id', $class_section_id);
        $this->db->where('student_session.session_id', $this->current_session);
        $this->db->where('users.role', 'student');
        $this->db->where('students.is_active', 'yes');
        $this->db->order_by('students.id', 'desc');
        $query = $this->db->get();
        return $query->result_array();
    }
}
