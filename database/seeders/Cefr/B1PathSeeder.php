<?php

namespace Database\Seeders\Cefr;

use App\Enums\LanguageLevel;

class B1PathSeeder extends CefrPathSeeder
{
    protected function level(): LanguageLevel
    {
        return LanguageLevel::B1;
    }

    protected function pathName(): string
    {
        return 'Bulgarian in Real Life';
    }

    protected function lessons(): array
    {
        return [
            [
                'name' => 'Making Connections',
                'description' => 'Introduce people politely, choose between "ти" and "Вие", and soften requests with "бих".',
                'exercises' => [
                    self::pairs('Match the meeting verbs', 'Match each English phrase to its Bulgarian equivalent.', [
                        ['To introduce oneself', 'представям се'],
                        ['To get acquainted', 'запознавам се'],
                        ['Acquaintance', 'познат'],
                        ['To greet', 'поздравявам'],
                        ['To shake hands', 'ръкувам се'],
                    ]),
                    self::fill('May I introduce', '__ ли да ви представя моя колега?', ['Бихте', 'Може', 'Искам'], 1,
                        '"Може ли да ви представя моя колега?" means "May I introduce my colleague to you?" "Може ли да..." is the usual way to ask permission.'),
                    self::trueFalse('True or false: Bih iskal', '"Бих искал" is a more polite way of saying "искам" (I want).', true,
                        'Correct. "Бих искал" (a woman says "бих искала") means "I would like" and softens a request.'),
                    self::fill('We finally met', 'Радвам се, че най-после се __ лично.', ['запознаваме', 'запознаем', 'запознахме'], 2,
                        '"Радвам се, че най-после се запознахме лично." means "I am glad we finally met in person." It happened once and is finished, so the past perfective form fits.'),
                    self::trueFalse('True or false: Formal email', 'In a formal email to a new business contact it is normal to write "ти".', false,
                        'Formal writing uses "Вие", and it is capitalised when you address one person.'),
                    self::pairs('Match the polite phrases', 'Match each English phrase to its Bulgarian equivalent.', [
                        ['Sorry to interrupt', 'Извинете, че ви прекъсвам'],
                        ['With pleasure', 'С удоволствие'],
                        ['Allow me', 'Позволете ми'],
                        ['Pleased to meet you', 'Приятно ми е да се запознаем'],
                        ['Business card', 'визитка'],
                    ]),
                    self::fill('Shall we use ti', 'Може ли да си __ на ти?', ['кажем', 'говорим', 'говори'], 1,
                        '"Може ли да си говорим на ти?" means "Shall we switch to the informal ti?" It is how Bulgarians offer to drop the formal Вие.'),
                ],
            ],
            [
                'name' => 'Life Stories',
                'description' => 'Tell your life story and choose between the two past tenses: background or event.',
                'exercises' => [
                    self::pairs('Match the life words', 'Match each English word to its Bulgarian equivalent.', [
                        ['Childhood', 'детство'],
                        ['Memory', 'спомен'],
                        ['To grow up', 'пораствам'],
                        ['To move house', 'премествам се'],
                        ['To remember', 'спомням си'],
                    ]),
                    self::fill('Every summer', 'Когато бях малък, всяко лято __ на село при баба.', ['отидох', 'ходех', 'ще ходя'], 1,
                        '"Когато бях малък, всяко лято ходех на село при баба." means "When I was little, every summer I went to my grandma\'s village." A repeated action in the past takes the imperfect.'),
                    self::trueFalse('True or false: Two past tenses', 'The imperfect ("четях") sets the scene or repeats, while the aorist ("прочетох") reports a finished event.', true,
                        'Correct. "Четях, когато той дойде" means "I was reading when he came".'),
                    self::fill('While I was cooking', 'Докато __ вечеря, телефонът звънна.', ['готвех', 'сготвих', 'готвя'], 0,
                        '"Докато готвех вечеря, телефонът звънна." means "While I was cooking dinner, the phone rang." The action in progress takes the imperfect.'),
                    self::trueFalse('True or false: Izrasnah', '"Израснах в малък град." means "I was born in a small town."', false,
                        'It means "I grew up in a small town". "I was born" is "Родих се".'),
                    self::pairs('Match the life events', 'Match each English phrase to its Bulgarian equivalent.', [
                        ['To retire', 'пенсионирам се'],
                        ['To emigrate', 'емигрирам'],
                        ['To settle down', 'установявам се'],
                        ['To change jobs', 'сменям работата си'],
                        ['To graduate', 'завършвам'],
                    ]),
                    self::fill('I used to stay late', 'Като студент често __ до късно в библиотеката.', ['постоях', 'стоя', 'стоях'], 2,
                        '"Като студент често стоях до късно в библиотеката." means "As a student I often stayed late in the library." Често marks a habit, which takes the imperfect.'),
                ],
            ],
            [
                'name' => 'Family Matters',
                'description' => 'Relationships and in-laws, and the reflexive possessive "си" (one\'s own).',
                'exercises' => [
                    self::pairs('Match the relationship words', 'Match each English word to its Bulgarian equivalent.', [
                        ['Relationship', 'връзка'],
                        ['Divorce', 'развод'],
                        ['To get engaged', 'сгодявам се'],
                        ["Mother-in-law (husband's mother)", 'свекърва'],
                        ["Father-in-law (wife's father)", 'тъст'],
                    ]),
                    self::fill('His own mother', 'Иван се обади на майка __ вчера.', ['му', 'си', 'ѝ'], 1,
                        '"Иван се обади на майка си вчера." means "Ivan called his (own) mother yesterday." Си points back to the subject; "майка му" would be another man\'s mother.'),
                    self::trueFalse('True or false: Kolata mu', '"Петър взе колата му." means Peter took his own car.', false,
                        'Му points to another man. Peter\'s own car is "колата си": "Петър взе колата си."'),
                    self::fill('Her boyfriend', 'Сестра ми и __ приятел се сгодиха.', ['неговият', 'техният', 'нейният'], 2,
                        '"Сестра ми и нейният приятел се сгодиха." means "My sister and her boyfriend got engaged."'),
                    self::trueFalse('True or false: Svekarva', '"Свекърва" is the husband\'s mother, from the wife\'s point of view.', true,
                        'Correct. The wife\'s mother is "тъща" from the husband\'s point of view.'),
                    self::pairs('Match the possessives', 'Match each English phrase to its Bulgarian equivalent.', [
                        ['Her husband', 'мъжът ѝ'],
                        ['Their children', 'децата им'],
                        ['Our parents', 'родителите ни'],
                        ['Your (plural) home', 'домът ви'],
                        ['My relatives', 'роднините ми'],
                    ]),
                    self::fill('Their own parents', 'Мария и Петър посетиха родителите __ в неделя.', ['им', 'си', 'ѝ'], 1,
                        '"Мария и Петър посетиха родителите си в неделя." means "Maria and Petar visited their (own) parents on Sunday." "Родителите им" would be someone else\'s parents.'),
                ],
            ],
            [
                'name' => 'Schedules and Routines',
                'description' => 'How often things happen, and when to use the perfective aspect after "да".',
                'exercises' => [
                    self::pairs('Match the frequency words', 'Match each English word to its Bulgarian equivalent.', [
                        ['Usually', 'обикновено'],
                        ['Rarely', 'рядко'],
                        ['Sometimes', 'понякога'],
                        ['Always', 'винаги'],
                        ['Never', 'никога'],
                    ]),
                    self::fill('Finish the report', 'Трябва да __ отчета до петък.', ['завършвам', 'завърша', 'завършвах'], 1,
                        '"Трябва да завърша отчета до петък." means "I have to finish the report by Friday." One completed result takes the perfective.'),
                    self::trueFalse('True or false: Da + perfective', 'After "да", a perfective verb usually means one complete action: "Искам да купя хляб."', true,
                        'Correct. "Искам да купувам хляб оттук" would mean buying it regularly.'),
                    self::fill('Every morning I run', 'Всяка сутрин __ по половин час.', ['изтичам', 'изтичах', 'тичам'], 2,
                        '"Всяка сутрин тичам по половин час." means "Every morning I run for half an hour." A habit takes the imperfective.'),
                    self::trueFalse('True or false: Nikoga', '"Никога" works without "не": "Никога ходя на кино."', false,
                        'Bulgarian needs the double negative: "Никога не ходя на кино."'),
                    self::pairs('Match the routine phrases', 'Match each English phrase to its Bulgarian equivalent.', [
                        ['In the morning', 'сутринта'],
                        ['At lunchtime', 'на обяд'],
                        ['On weekdays', 'в делничните дни'],
                        ['At the weekend', 'през уикенда'],
                        ['Once a week', 'веднъж седмично'],
                    ]),
                    self::fill('I usually go swimming', '__ ходя на плуване два пъти седмично.', ['Вчера', 'Обикновено', 'Никога'], 1,
                        '"Обикновено ходя на плуване два пъти седмично." means "I usually go swimming twice a week." Никога would need "не", and вчера needs the past.'),
                ],
            ],
            [
                'name' => 'Moving House',
                'description' => 'Tenancy agreements, bills and repairs, with impersonal and "се" sentences.',
                'exercises' => [
                    self::pairs('Match the tenancy words', 'Match each English phrase to its Bulgarian equivalent.', [
                        ['Plumber', 'водопроводчик'],
                        ['Electricity bill', 'сметка за ток'],
                        ['Tenancy agreement', 'договор за наем'],
                        ['Neighbour', 'съсед'],
                        ['Deposit', 'депозит'],
                    ]),
                    self::fill('The tap is leaking', 'Кранът в банята __ от два дни.', ['текат', 'тече', 'течем'], 1,
                        '"Кранът в банята тече от два дни." means "The bathroom tap has been leaking for two days." Bulgarian uses the present with "от" for something still going on.'),
                    self::trueFalse('True or false: Dava se pod naem', '"Апартаментът се дава под наем." means "The flat is for rent."', true,
                        'Correct. "Се дава под наем" is literally "is given under rent".'),
                    self::fill('They will cut the power', 'Ако не платиш сметката, ще ти __ тока.', ['спра', 'спреш', 'спрат'], 2,
                        '"Ако не платиш сметката, ще ти спрат тока." means "If you do not pay the bill, they will cut off your electricity." The unnamed "they" takes the plural.'),
                    self::trueFalse('True or false: Otgore', '"Съседите отгоре" are the neighbours downstairs.', false,
                        'Отгоре means "from above", so these are the upstairs neighbours. Downstairs is "отдолу".'),
                    self::pairs('Match the repair words', 'Match each English phrase to its Bulgarian equivalent.', [
                        ['To break down', 'развалям се'],
                        ['To fix', 'поправям'],
                        ['To replace', 'сменям'],
                        ['Mould', 'мухъл'],
                        ['Noise', 'шум'],
                    ]),
                    self::fill('The washing machine broke', 'Пералнята се __ и трябва да извикаме техник.', ['развалят', 'развалих', 'развали'], 2,
                        '"Пералнята се развали и трябва да извикаме техник." means "The washing machine broke down and we need to call a technician."'),
                ],
            ],
            [
                'name' => 'Being a Good Host',
                'description' => 'Offer food, propose toasts and give friendly commands with "нека".',
                'exercises' => [
                    self::pairs('Match the host words', 'Match each English word to its Bulgarian equivalent.', [
                        ['To treat (someone)', 'черпя'],
                        ['Homemade', 'домашен'],
                        ['Appetizer', 'мезе'],
                        ['To pour', 'наливам'],
                        ['To taste', 'опитвам'],
                    ]),
                    self::fill('Have more salad', 'Заповядайте, __ си още от салатата!', ['сипвате', 'сипете', 'сипахте'], 1,
                        '"Заповядайте, сипете си още от салатата!" means "Please, help yourselves to more salad!" Сипете is the polite command form.'),
                    self::trueFalse('True or false: Cherpya', '"Черпя" means you pay for food or drinks for others, for example on your birthday.', true,
                        'Correct. In Bulgaria the person celebrating treats everyone else: "Днес аз черпя!"'),
                    self::fill('Let us raise a toast', '__ да вдигнем тост за домакините!', ['Нека', 'Дано', 'Ако'], 0,
                        '"Нека да вдигнем тост за домакините!" means "Let us raise a toast to the hosts!" Нека means "let".'),
                    self::trueFalse('True or false: Ne se pritesnyavaite', 'You say "Не се притеснявайте" when you want the host to go to more trouble for you.', false,
                        'It means "Please don\'t worry" or "Don\'t go to any trouble", so it tells the host not to bother.'),
                    self::pairs('Match the Bulgarian dishes', 'Match each description to the Bulgarian dish.', [
                        ['Cheese pastry', 'баница'],
                        ['Stuffed peppers', 'пълнени чушки'],
                        ['Yoghurt', 'кисело мляко'],
                        ['Cold cucumber soup', 'таратор'],
                        ['Grilled mince roll', 'кебапче'],
                    ]),
                    self::fill('What tarator is made of', 'Таратор се прави от краставици, __ и чесън.', ['сирене', 'кисело мляко', 'ориз'], 1,
                        '"Таратор се прави от краставици, кисело мляко и чесън." means "Tarator is made from cucumbers, yoghurt and garlic."'),
                ],
            ],
            [
                'name' => 'Job Hunting',
                'description' => 'CVs and interviews, the conditional "бих" and reporting questions with "дали".',
                'exercises' => [
                    self::pairs('Match the job-hunting words', 'Match each English phrase to its Bulgarian equivalent.', [
                        ['Job interview', 'интервю за работа'],
                        ['CV', 'автобиография'],
                        ['Experience', 'опит'],
                        ['Employer', 'работодател'],
                        ['Application', 'кандидатура'],
                    ]),
                    self::fill('I would like to work', 'Бих __ да работя в международен екип.', ['искам', 'искал', 'исках'], 1,
                        '"Бих искал да работя в международен екип." means "I would like to work in an international team." Бих is followed by the -л form: искал, or искала for a woman.'),
                    self::trueFalse('True or false: Reported speech', '"Каза, че търси работа." means "He said he was looking for a job."', true,
                        'Correct. Bulgarian keeps the tense of the original words, so the present "търси" stays.'),
                    self::fill('Whether I speak German', 'Интервюиращият ме попита __ говоря немски.', ['че', 'ако', 'дали'], 2,
                        '"Интервюиращият ме попита дали говоря немски." means "The interviewer asked me whether I speak German." Reported yes/no questions use дали.'),
                    self::trueFalse('True or false: Naznachiha me', '"Назначиха ме." means "I was fired."', false,
                        '"Назначиха ме" means "I was hired". "I was fired" is "Уволниха ме".'),
                    self::pairs('Match the contract terms', 'Match each English phrase to its Bulgarian equivalent.', [
                        ['Full-time', 'пълен работен ден'],
                        ['Part-time', 'непълен работен ден'],
                        ['Remote work', 'дистанционна работа'],
                        ['Probation period', 'изпитателен срок'],
                        ['Notice period', 'предизвестие'],
                    ]),
                    self::fill('If I had more experience', 'Ако __ повече опит, бих кандидатствал за тази позиция.', ['имам', 'имах', 'имаш'], 1,
                        '"Ако имах повече опит, бих кандидатствал..." means "If I had more experience, I would apply for this position." An unreal condition takes the past form имах.'),
                ],
            ],
            [
                'name' => 'Science Around Us',
                'description' => 'Planets and space, and comparing equals with "толкова ... колкото".',
                'exercises' => [
                    self::pairs('Match the space words', 'Match each English word to its Bulgarian equivalent.', [
                        ['Planet', 'планета'],
                        ['Solar system', 'Слънчева система'],
                        ['Gravity', 'гравитация'],
                        ['Orbit', 'орбита'],
                        ['Telescope', 'телескоп'],
                    ]),
                    self::fill('The largest planet', 'Юпитер е __ планета в Слънчевата система.', ['по-голямата', 'най-голямата', 'голямата'], 1,
                        '"Юпитер е най-голямата планета в Слънчевата система." means "Jupiter is the largest planet in the Solar System."'),
                    self::trueFalse('True or false: Tolkova kolkoto', '"Толкова ... колкото" shows two things are equal: "Той е толкова висок, колкото брат си."', true,
                        'Correct. The sentence means "He is as tall as his brother".'),
                    self::fill('Around its axis', 'Земята се върти около __ си за едно денонощие.', ['осите', 'ос', 'оста'], 2,
                        '"Земята се върти около оста си за едно денонощие." means "The Earth turns on its axis in one day and night." With си, the noun takes the article: оста си.'),
                    self::trueFalse('True or false: Denonoshtie', '"Денонощие" means a period of twelve hours.', false,
                        'A денонощие is a full day and night, 24 hours.'),
                    self::pairs('Match the units', 'Match each unit to its Bulgarian word.', [
                        ['Kilometre', 'километър'],
                        ['Light year', 'светлинна година'],
                        ['Degree', 'градус'],
                        ['Kilogram', 'килограм'],
                        ['Second', 'секунда'],
                    ]),
                    self::fill('In about eight minutes', 'Светлината от Слънцето стига до Земята __ около осем минути.', ['от', 'през', 'за'], 2,
                        '"Светлината от Слънцето стига до Земята за около осем минути." means "Sunlight reaches the Earth in about eight minutes." За + time says how long something takes.'),
                ],
            ],
            [
                'name' => 'Deadlines and Forecasts',
                'description' => 'Weather forecasts and time clauses with "преди да", "след като" and "докато".',
                'exercises' => [
                    self::pairs('Match the forecast words', 'Match each English word to its Bulgarian equivalent.', [
                        ['Forecast', 'прогноза'],
                        ['Storm', 'буря'],
                        ['Fog', 'мъгла'],
                        ['Heat (weather)', 'жега'],
                        ['Frost', 'слана'],
                    ]),
                    self::fill('Before you leave', 'Обади ми се, __ тръгнеш.', ['след', 'преди да', 'докато'], 1,
                        '"Обади ми се, преди да тръгнеш." means "Call me before you leave." Преди да is followed by a verb.'),
                    self::trueFalse('True or false: Sled kato', '"След като" is followed by a completed action: "След като вечеряхме, излязохме."', true,
                        'Correct. The sentence means "After we had dinner, we went out".'),
                    self::fill('Below zero', 'Според прогнозата температурите ще __ под нулата.', ['падне', 'падат', 'паднат'], 2,
                        '"Според прогнозата температурите ще паднат под нулата." means "According to the forecast, temperatures will drop below zero." Температурите is plural.'),
                    self::trueFalse('True or false: Magla', '"Мъгла" means "rainbow".', false,
                        '"Мъгла" means "fog". A rainbow is "дъга".'),
                    self::pairs('Match the time links', 'Match each English conjunction to its Bulgarian equivalent.', [
                        ['Before', 'преди да'],
                        ['After', 'след като'],
                        ['While', 'докато'],
                        ['Until', 'докато не'],
                        ['As soon as', 'щом'],
                    ]),
                    self::fill('As soon as you finish', '__ свършиш работа, обади ми се.', ['Докато', 'Щом', 'Преди'], 1,
                        '"Щом свършиш работа, обади ми се." means "As soon as you finish work, call me." Преди would need "да".'),
                ],
            ],
            [
                'name' => 'Traditions and Customs',
                'description' => 'Kukeri, fire dancers and wine growers, with "за да" and the passive with "се".',
                'exercises' => [
                    self::pairs('Match the tradition words', 'Match each English word to its Bulgarian equivalent.', [
                        ['Custom', 'обичай'],
                        ['Mask', 'маска'],
                        ['Holiday', 'празник'],
                        ['Wine grower', 'лозар'],
                        ['Fire dancing', 'нестинарство'],
                    ]),
                    self::fill('To chase away spirits', 'Кукерите носят маски, за да __ злите духове.', ['прогонват', 'прогонят', 'прогониха'], 1,
                        '"Кукерите носят маски, за да прогонят злите духове." means "The kukeri wear masks to chase away evil spirits." За да + perfective expresses a purpose.'),
                    self::trueFalse('True or false: Trifon Zarezan', 'Трифон Зарезан, on 14 February, is the holiday of wine growers.', true,
                        'Correct. On Трифон Зарезан the vines are pruned and the new wine is blessed.'),
                    self::fill('Worn until spring', 'Мартеницата се __ до пролетта.', ['носим', 'носят', 'носи'], 2,
                        '"Мартеницата се носи до пролетта." means "The martenitsa is worn until spring." Се + third person makes a passive. Traditionally you take it off when you see a stork or a tree in blossom.'),
                    self::trueFalse('True or false: Nestinari', 'Nestinari (нестинари) walk on water on Christmas Eve.', false,
                        'Nestinari dance barefoot on glowing embers, on the feast of Saints Constantine and Helena.'),
                    self::pairs('Match the holidays', 'Match each holiday to its Bulgarian name.', [
                        ['Christmas', 'Коледа'],
                        ['Easter', 'Великден'],
                        ['St George\'s Day', 'Гергьовден'],
                        ['New Year\'s Day', 'Нова година'],
                        ['1 March', 'Баба Марта'],
                    ]),
                    self::fill('Lamb on St George\'s Day', 'На Гергьовден традиционно се яде печено __ с ориз.', ['прасе', 'пиле', 'агне'], 2,
                        '"На Гергьовден традиционно се яде печено агне..." means "On St George\'s Day, roast lamb is traditionally eaten..."'),
                ],
            ],
        ];
    }
}
