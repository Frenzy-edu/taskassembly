<?php

namespace Database\Seeders;

use App\Models\Task;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class TaskSeeder extends Seeder
{
    // Seed the task list with realistic duties for a Cameroonian secondary school principal.
    public function run(): void
    {
        $today = Carbon::today();

        $tasks = [
            [
                'title' => 'Chair weekly staff meeting',
                'description' => 'Review last week\'s attendance records, discuss the upcoming First Sequence exams, and address the leaking roof in Block C reported by the discipline master.',
                'status' => Task::STATUS_COMPLETED,
                'start_date' => $today->copy()->subDays(14),
                'due_date' => $today->copy()->subDays(14),
            ],
            [
                'title' => 'Submit First Sequence exam timetable to Regional Delegation',
                'description' => 'Finalize the sequence exam timetable for Forms 1-5 and Lower/Upper Sixth, then submit to the Regional Delegation of Secondary Education for approval.',
                'status' => Task::STATUS_COMPLETED,
                'start_date' => $today->copy()->subDays(12),
                'due_date' => $today->copy()->subDays(9),
            ],
            [
                'title' => 'Follow up on unpaid PTA fees for Term 1',
                'description' => 'Work with the bursar to compile the list of students with outstanding Parent-Teacher Association dues and send reminder letters home.',
                'status' => Task::STATUS_IN_PROGRESS,
                'start_date' => $today->copy()->subDays(10),
                'due_date' => $today->copy()->addDays(2),
            ],
            [
                'title' => 'Inspect Form 5 and Upper Sixth GCE mock scripts',
                'description' => 'Spot-check marked mock examination scripts for the GCE O\'Level and A\'Level candidates before results are released to parents.',
                'status' => Task::STATUS_IN_PROGRESS,
                'start_date' => $today->copy()->subDays(5),
                'due_date' => $today->copy()->addDays(3),
            ],
            [
                'title' => 'Prepare welcome remarks for Regional Delegate\'s inspection visit',
                'description' => 'The Regional Delegate of Secondary Education is visiting next week for a routine inspection; prepare the school\'s statistics, staff attendance register, and welcome address.',
                'status' => Task::STATUS_PENDING,
                'start_date' => null,
                'due_date' => $today->copy()->addDays(6),
            ],
            [
                'title' => 'Resolve dormitory overcrowding in the girls\' hostel',
                'description' => 'Meet with the matron and bursar to redistribute bed space following the admission of 15 new boarding students this term.',
                'status' => Task::STATUS_PENDING,
                'start_date' => null,
                'due_date' => $today->copy()->addDays(4),
            ],
            [
                'title' => 'Approve Form 4 Geography field trip to Mount Cameroon',
                'description' => 'Review the risk assessment and parental consent forms submitted by the Geography department before signing off on the excursion.',
                'status' => Task::STATUS_PENDING,
                'start_date' => null,
                'due_date' => $today->copy()->addDays(10),
            ],
            [
                'title' => 'Interview shortlisted candidates for the vacant Mathematics post',
                'description' => 'Two candidates recommended by MINESEC are available for interview to replace Mr. Nkeng, who was transferred to Bamenda.',
                'status' => Task::STATUS_PENDING,
                'start_date' => null,
                'due_date' => $today->copy()->addDays(7),
            ],
            [
                'title' => 'Finalize end-of-term report cards for Forms 1-3',
                'description' => 'Verify that all subject teachers have submitted marks into the report card system before cards are printed and signed.',
                'status' => Task::STATUS_PENDING,
                'start_date' => $today->copy()->addDays(1),
                'due_date' => $today->copy()->addDays(12),
            ],
            [
                'title' => 'Organize Disciplinary Council for repeated truancy cases',
                'description' => 'Convene the Discipline Master, class teachers, and affected parents to review three students flagged for repeated absenteeism this term.',
                'status' => Task::STATUS_PENDING,
                'start_date' => null,
                'due_date' => $today->copy()->addDays(5),
            ],
            [
                'title' => 'Review and sign termly budget for the bursary office',
                'description' => 'Go through the proposed Term 2 budget covering stationery, laboratory consumables, and maintenance before forwarding it to the Board of Governors.',
                'status' => Task::STATUS_PENDING,
                'start_date' => null,
                'due_date' => $today->copy()->addDays(8),
            ],
            [
                'title' => 'Coordinate National Day (20th May) rehearsals',
                'description' => 'Liaise with the Physical Education department and the school band to schedule rehearsals for the National Day march-past.',
                'status' => Task::STATUS_PENDING,
                'start_date' => null,
                'due_date' => $today->copy()->addDays(20),
            ],
            [
                'title' => 'Procure new laboratory equipment for the Physics department',
                'description' => 'Obtain quotations from approved suppliers in Douala for replacement beakers, burettes, and a retort stand set damaged during last term\'s practicals.',
                'status' => Task::STATUS_PENDING,
                'start_date' => null,
                'due_date' => $today->copy()->addDays(15),
            ],
            [
                'title' => 'Hold Parent-Teacher Association general assembly',
                'description' => 'Present the term\'s academic performance summary and discuss the proposed construction of two additional classrooms with parents.',
                'status' => Task::STATUS_PENDING,
                'start_date' => null,
                'due_date' => $today->copy()->addDays(18),
            ],
            [
                'title' => 'Verify textbook distribution for the bilingual stream',
                'description' => 'Confirm that both the English-speaking and French-speaking streams received their full allocation of textbooks from the Regional Pedagogic Inspectorate.',
                'status' => Task::STATUS_COMPLETED,
                'start_date' => $today->copy()->subDays(20),
                'due_date' => $today->copy()->subDays(18),
            ],
            [
                'title' => 'Address water shortage affecting the school borehole',
                'description' => 'Engage the contractor who installed the borehole last year to diagnose the pump failure affecting water supply to the kitchen and toilets.',
                'status' => Task::STATUS_COMPLETED,
                'start_date' => $today->copy()->subDays(8),
                'due_date' => $today->copy()->subDays(6),
            ],
            [
                'title' => 'Compile staff attendance report for the Regional Delegation',
                'description' => 'Collate the monthly staff attendance registers from all Heads of Department for submission to the Regional Delegation of Secondary Education.',
                'status' => Task::STATUS_IN_PROGRESS,
                'start_date' => $today->copy()->subDays(2),
                'due_date' => $today->copy()->addDays(1),
            ],
            [
                'title' => 'Plan Form 5 and Upper Sixth GCE registration drive',
                'description' => 'Set up a registration desk with the exams officer to assist candidates with GCE Board online registration and fee payment via Express Union.',
                'status' => Task::STATUS_PENDING,
                'start_date' => null,
                'due_date' => $today->copy()->addDays(25),
            ],
            [
                'title' => 'Review proposal for solar-powered backup lighting',
                'description' => 'Assess a proposal from a Yaounde-based solar company to install backup lighting in the administrative block and library following frequent ENEO power cuts.',
                'status' => Task::STATUS_PENDING,
                'start_date' => null,
                'due_date' => $today->copy()->addDays(22),
            ],
            [
                'title' => 'Sign off on Founders\' Day celebration program',
                'description' => 'Approve the final program for the school\'s anniversary celebration, including the cultural display by the bilingual club and the alumni association\'s address.',
                'status' => Task::STATUS_PENDING,
                'start_date' => null,
                'due_date' => $today->copy()->addDays(30),
            ],
        ];

        foreach ($tasks as $task) {
            Task::create($task);
        }
    }
}
