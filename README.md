# README FILE

## Task is to run the reports based on the student assessment data being loaded from the data files places in the **data** folder

Location of data files - data\

## Steps to run the application
1. Clone the application from git repository
2. Run the ***composer install*** to install the framework
3. In command line, run the following laravel command to run the application
    1. ***php artisan run_reports***

4. If there is no error, then on running the above command, the system will prompt for entering the following data
    1. Student ID,
    2. Type of report to run - 1 for Diagnostic, 2 for Progress and 3 for Feedback


## Assumptions
**Data validation**
1. If no user found or data files are not placed, or
2. If any required files is missing or having empty data, then report will return the following message
**No records found for the student or no data files found**

**Progress report**
1. Current data only deals with single assessment, the code is written to handle multiple assessments when running the reports. the output will be displayed in same format, individually for each assessment type. 


## FUTURE ASPECTS
- The application can be extended or modified to have individual report class for each report type using namespace or abstract classes to have same structure.
- Testing can be included to the classes to improve the functionality of the application

