<a href="https://travis-ci.org/catalyst/moodle-tool_cohortautoroles">
<img src="https://travis-ci.org/catalyst/moodle-tool_cohortautoroles.svg?branch=master">
</a>

# Assign auto roles to cohort

This plugin is based on the core tool_cohortroles plugin and allows the selection of a role rather than an individual to assign a manager/mentor/parent to a user.

If a user is assigned the selected role at any level - (site/course/category etc) they will be assigned as the mentor/manager/parent to all users within the cohorts selected they are also a member of.

####Example:
* Cohort "Engineering" has the users, "John, Mary, Steven, George"
* Cohort "6th Floor" has the users "John, David, Simon"
* John is enrolled in course 1 with the custom role "Support"
* David is assigned the custom role "support" at the site-level.
* A Parent/Mentor/manager role has been created in the system to allow user-level role assignments - see: https://docs.moodle.org/en/Parent_role
* The cohortautoroles plugin is configured to find all users in the "support" role, assign them to the parent/manger/mentor role. 

John will be automatically assigned as the mentor to "Mary, Steven, George, David, Simon"
David will be automatically assigned as the mentor to "John, Simon"

Installation
------------

1. Install the plugin the same as any standard moodle plugin either via the
Moodle plugin directory, or you can use git to clone it into your source:

     `git clone git@github.com:catalyst/moodle-tool_cohortautoroles.git admin/tool/cohortautoroles

    Or install via the Moodle plugin directory:
    
     https://moodle.org/plugins/tool_cohortautoroles

2. Then run the Moodle upgrade

If you have issues please log them in github here:

https://github.com/catalyst/moodle-tool_cohortautoroles/issues


How to use
----------

Go to `Dashboard ► Site administration ► Users ► Permissions ► Assign auto roles to cohort `

Feedback and issues
-------------------

Please raise any issues in github:
https://github.com/catalyst/moodle-tool_cohortautoroles/issues

Pull requests are welcome :)

If you would like to sponsor work on this plugin please contact Catalyst IT:
https://www.catalyst.net.nz