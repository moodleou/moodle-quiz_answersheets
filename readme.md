# Export quiz attempts report

This 'report' is actually a tool like the standard Response quiz report, 
but which lets Staff to generate a paper version of quizzes and create attempts
for Students.

In addition, there is slightly primitive support for downloading all the attempts
at a quiz, using the helper script https://github.com/moodleou/save-answersheets. 

## Installation and set-up

This plugin should be compatible with Moodle 3.4+

### Install using git

Or you can install using git. Type this commands in the root of your Moodle install

    git clone https://github.com/moodleou/moodle-quiz_answersheets.git mod/quiz/report/answersheets
    echo '/mod/quiz/report/answersheets/' >> .git/info/exclude
    
Then run the moodle update process
Site administration > Notifications
