<?php

namespace Database\Seeders\Cefr;

use App\Enums\LanguageLevel;

class C1PathSeeder extends CefrPathSeeder
{
    protected function level(): LanguageLevel
    {
        return LanguageLevel::C1;
    }

    protected function pathName(): string
    {
        return 'Advanced Bulgarian';
    }

    protected function lessons(): array
    {
        return [
            [
                'name' => 'Networking and Small Talk',
                'description' => 'Linking words and set phrases that make conversation flow.',
                'exercises' => [
                    self::pairs('Match the linking words', 'Match each English linking word to its Bulgarian equivalent.', [
                        ['Nevertheless', 'въпреки това'],
                        ['Moreover', 'освен това'],
                        ['In other words', 'с други думи'],
                        ['On the contrary', 'напротив'],
                        ['As a matter of fact', 'всъщност'],
                    ]),
                    self::fill('Not only late', 'Не само че закъсня, __ и не се извини.', ['или', 'ами', 'нито'], 1,
                        '"Не само че закъсня, ами и не се извини." means "Not only was he late, he did not even apologise." The pair is "не само ..., ами/но и ...".'),
                    self::trueFalse('True or false: Naprotiv', '"Напротив" strongly contradicts a negative question: "Не ти ли хареса?" "Напротив, много ми хареса!"', true,
                        'Correct. It means "On the contrary".'),
                    self::fill('Stay in touch', 'Ще се радвам да __ контакт и в бъдеще.', ['правим', 'водим', 'поддържаме'], 2,
                        '"Ще се радвам да поддържаме контакт и в бъдеще." means "I would be glad to stay in touch in future." The collocation is "поддържам контакт".'),
                    self::trueFalse('True or false: Vsashtnost', '"Всъщност" means "at first".', false,
                        '"Всъщност" means "actually" or "in fact". "At first" is "отначало".'),
                    self::pairs('Match the opinion phrases', 'Match each English phrase to its Bulgarian equivalent.', [
                        ['In my view', 'по мое мнение'],
                        ['As far as I know', 'доколкото знам'],
                        ['To be honest', 'честно казано'],
                        ['By the way', 'между другото'],
                        ['In short', 'накратко'],
                    ]),
                    self::fill('As far as I know', '__ знам, срещата е отложена за четвъртък.', ['Колкото', 'Доколкото', 'Докато'], 1,
                        '"Доколкото знам, срещата е отложена за четвъртък." means "As far as I know, the meeting has been moved to Thursday."'),
                ],
            ],
            [
                'name' => 'Memoir and Autobiography',
                'description' => 'The -йки adverbial participle and the doubting "бил бил" form.',
                'exercises' => [
                    self::pairs('Match the -йки forms', 'Match each English form to its Bulgarian adverbial participle.', [
                        ['Walking', 'вървейки'],
                        ['Reading', 'четейки'],
                        ['Smiling', 'усмихвайки се'],
                        ['Knowing', 'знаейки'],
                        ['Looking back', 'поглеждайки назад'],
                    ]),
                    self::fill('Looking back', '__ назад, осъзнавам колко много съм научил.', ['Погледнал', 'Поглеждайки', 'Поглеждащ'], 1,
                        '"Поглеждайки назад, осъзнавам колко много съм научил." means "Looking back, I realise how much I have learned." The -йки form describes an action at the same time as the main verb.'),
                    self::trueFalse('True or false: Dangling participle', 'The -йки participle can have a different subject from the main verb: "Четейки книгата, телефонът звънна."', false,
                        'The participle must share the main verb\'s subject, and a phone cannot read. Say "Докато четях книгата, телефонът звънна."'),
                    self::fill('A great scientist, supposedly', 'Той бил __ голям учен, а никой не е чувал за него!', ['беше', 'е', 'бил'], 2,
                        '"Той бил бил голям учен..." means "He is supposedly a great scientist...", said with doubt. The doubled бил repeats a claim you do not believe.'),
                    self::trueFalse('True or false: Reported about yourself', 'You can use the renarrative about yourself, as in "Бил съм много палав като дете", to pass on what others told you.', true,
                        'Correct. It means "Apparently I was very naughty as a child", something you only know from others.'),
                    self::pairs('Match the memoir words', 'Match each English word to its Bulgarian equivalent.', [
                        ['Nostalgia', 'носталгия'],
                        ['To recollect', 'припомням си'],
                        ['Turning point', 'повратна точка'],
                        ['Hardship', 'лишения'],
                        ['Reflection', 'размисъл'],
                    ]),
                    self::fill('Every evening he wrote', 'Имаше период, в който всяка вечер __ писма на баба си.', ['написа', 'пише', 'пишеше'], 2,
                        '"Имаше период, в който всяка вечер пишеше писма на баба си." means "There was a time when every evening he wrote letters to his grandmother." A past habit takes the imperfect.'),
                ],
            ],
            [
                'name' => 'Family and Society',
                'description' => 'Demographics and social policy, and the difference between cause words.',
                'exercises' => [
                    self::pairs('Match the society words', 'Match each English phrase to its Bulgarian equivalent.', [
                        ['Birth rate', 'раждаемост'],
                        ['Ageing population', 'застаряващо население'],
                        ['Single-parent family', 'еднородителско семейство'],
                        ['Social benefits', 'социални помощи'],
                        ['Maternity leave', 'отпуск по майчинство'],
                    ]),
                    self::fill('Taking measures', 'Правителството __ мерки за насърчаване на раждаемостта.', ['изпълни', 'предприе', 'постави'], 1,
                        '"Правителството предприе мерки за насърчаване на раждаемостта." means "The government took measures to encourage the birth rate." The collocation is "предприемам мерки".'),
                    self::trueFalse('True or false: Kakto takа i', '"Както ..., така и ..." means "both ... and ...".', true,
                        'Correct. "Засяга както града, така и селото" means "It affects both the city and the village".'),
                    self::fill('As a consequence', '__ на застаряването населението в селата намалява.', ['Благодарение', 'Въпреки', 'Вследствие'], 2,
                        '"Вследствие на застаряването населението в селата намалява." means "As a result of ageing, the village population is shrinking." Благодарение на is kept for good outcomes.'),
                    self::trueFalse('True or false: Blagodarenie na', '"Благодарение на" works for any cause, good or bad: "Благодарение на кризата загубих работата си."', false,
                        '"Благодарение на" implies gratitude, so a bad cause takes "поради", "заради" or "вследствие на".'),
                    self::pairs('Match the social issues', 'Match each English term to its Bulgarian equivalent.', [
                        ['Unemployment', 'безработица'],
                        ['Inequality', 'неравенство'],
                        ['Emigration', 'емиграция'],
                        ['Welfare state', 'социална държава'],
                        ['Census', 'преброяване'],
                    ]),
                    self::fill('The latest census', 'Според последното __ населението на България е под седем милиона.', ['изброяване', 'преброяване', 'отброяване'], 1,
                        '"Според последното преброяване..." means "According to the latest census, Bulgaria\'s population is under seven million."'),
                ],
            ],
            [
                'name' => 'Productivity and Time',
                'description' => 'Time idioms, past unreal conditions and perfectives for typical repeated actions.',
                'exercises' => [
                    self::pairs('Match the time idioms', 'Match each English meaning to its Bulgarian idiom.', [
                        ['To kill time', 'убивам времето'],
                        ['At the last minute', 'в последния момент'],
                        ['Sooner or later', 'рано или късно'],
                        ['When pigs fly', 'на куково лято'],
                        ['Pressed for time', 'притиснат от времето'],
                    ]),
                    self::fill('The last moment', 'Той е свикнал да оставя всичко за __ момент.', ['последен', 'последния', 'последната'], 1,
                        '"Той е свикнал да оставя всичко за последния момент." means "He is used to leaving everything to the last minute."'),
                    self::trueFalse('True or false: Kukovo lyato', '"На куково лято" means "early next summer".', false,
                        'It means "never", literally "in the cuckoo\'s summer", much like "when pigs fly".'),
                    self::fill('If we had left earlier', 'Ако __ по-рано, нямаше да се наложи да бързаме.', ['тръгваме', 'тръгнахме', 'бяхме тръгнали'], 2,
                        '"Ако бяхме тръгнали по-рано, нямаше да се наложи да бързаме." means "If we had left earlier, we would not have had to hurry."'),
                    self::trueFalse('True or false: Repeated perfectives', 'In "Всяка сутрин той ще изпие едно кафе и ще тръгне", perfective verbs describe a typical, repeated sequence.', true,
                        'Correct. Ще + perfective can describe a habitual chain of actions: "Every morning he\'ll drink a coffee and set off."'),
                    self::pairs('Match the time verbs', 'Match each English phrase to its Bulgarian equivalent.', [
                        ['To oversleep', 'успивам се'],
                        ['To catch up (on work)', 'наваксвам'],
                        ['To drag things out', 'протакам'],
                        ['To rush', 'бързам'],
                        ['To manage in time', 'смогвам'],
                    ]),
                    self::fill('I overslept', 'Тази сутрин се __ и изпуснах автобуса.', ['наспах', 'успах', 'събудих'], 1,
                        '"Тази сутрин се успах и изпуснах автобуса." means "This morning I overslept and missed the bus." Се наспах means "I got enough sleep".'),
                ],
            ],
            [
                'name' => 'Urban Living',
                'description' => 'City planning and administrative style, with its preference for nouns over verbs.',
                'exercises' => [
                    self::pairs('Match the city words', 'Match each English phrase to its Bulgarian equivalent.', [
                        ['Municipality', 'община'],
                        ['Building permit', 'разрешение за строеж'],
                        ['Public transport', 'градски транспорт'],
                        ['Pedestrian zone', 'пешеходна зона'],
                        ['Green spaces', 'зелени площи'],
                    ]),
                    self::fill('Construction will begin', '__ на пешеходната зона ще започне през пролетта.', ['Изграждам', 'Изграждането', 'Изградена'], 1,
                        '"Изграждането на пешеходната зона ще започне през пролетта." means "Construction of the pedestrian zone will begin in spring."'),
                    self::trueFalse('True or false: Nominal style', 'Administrative Bulgarian prefers nouns to verbs: "извършване на проверка" instead of "проверявам".', true,
                        'Correct. Official texts often turn verbs into nouns: извършване, осъществяване, предоставяне.'),
                    self::fill('Protest against building', 'Жителите на квартала протестираха __ застрояването на парка.', ['върху', 'пред', 'срещу'], 2,
                        '"Жителите на квартала протестираха срещу застрояването на парка." means "Residents protested against building on the park."'),
                    self::trueFalse('True or false: Panelen blok', '"Панелен блок" is a luxury villa with a garden.', false,
                        'A панелен блок is a prefabricated concrete apartment block, common in Bulgarian cities.'),
                    self::pairs('Match the city problems', 'Match each English term to its Bulgarian equivalent.', [
                        ['Suburb', 'предградие'],
                        ['Traffic jam', 'задръстване'],
                        ['Parking space', 'паркомясто'],
                        ['Urban sprawl', 'неконтролирано разрастване на града'],
                        ['Underpass', 'подлез'],
                    ]),
                    self::fill('Traffic is restricted', 'Заради ремонта движението по булеварда е __ до края на месеца.', ['ограничаващо', 'ограничило', 'ограничено'], 2,
                        '"...движението по булеварда е ограничено до края на месеца" means "...traffic on the boulevard is restricted until the end of the month."'),
                ],
            ],
            [
                'name' => 'Hospitality Culture',
                'description' => 'Proverbs about guests and gifts, and what they say about Bulgarian manners.',
                'exercises' => [
                    self::pairs('Match the proverbs', 'Match each English meaning to the Bulgarian proverb.', [
                        ['Hospitality is sacred', 'Гост в къщи, Бог в къщи'],
                        ["Don't look a gift horse in the mouth", 'На харизан кон зъби не се гледат'],
                        ['Teamwork moves mountains', 'Сговорна дружина планина повдига'],
                        ['Out of sight, out of mind', 'Далеч от очите, далеч от сърцето'],
                        ['Better late than never', 'По-добре късно, отколкото никога'],
                    ]),
                    self::fill('A gift horse', 'На харизан кон __ не се гледат.', ['очи', 'зъби', 'крака'], 1,
                        '"На харизан кон зъби не се гледат." means "You don\'t check the teeth of a gift horse", so do not criticise a gift.'),
                    self::trueFalse('True or false: Sgovorna druzhina', '"Сговорна дружина планина повдига" means that people who work together can achieve anything.', true,
                        'Correct. Literally, "a like-minded company lifts a mountain".'),
                    self::fill('She insisted on treating us', 'Домакинята настоя да ни __ с домашна баница.', ['почерпим', 'почерпят', 'почерпи'], 2,
                        '"Домакинята настоя да ни почерпи с домашна баница." means "The hostess insisted on treating us to homemade banitsa."'),
                    self::trueFalse('True or false: Dalech ot ochite', '"Далеч от очите, далеч от сърцето" means that absence makes the heart grow fonder.', false,
                        'It means the opposite: out of sight, out of mind.'),
                    self::pairs('Match more proverbs', 'Match each English meaning to the Bulgarian proverb.', [
                        ['The early bird catches the worm', 'Който рано рани, две гърнета напълни'],
                        ['Every novelty soon fades', 'Всяко чудо за три дни'],
                        ['All that glitters is not gold', 'Не всичко, що блести, е злато'],
                        ['You can\'t do two big things at once', 'Две дини под една мишница не се носят'],
                        ['Nobody works on an empty stomach', 'Гладна мечка хоро не играе'],
                    ]),
                    self::fill('A hungry bear', 'Гладна мечка __ не играе.', ['танц', 'хоро', 'песен'], 1,
                        '"Гладна мечка хоро не играе." means "A hungry bear does not dance the horo": feed people before you expect work from them.'),
                ],
            ],
            [
                'name' => 'Negotiations and Business',
                'description' => 'Business collocations and softened proposals.',
                'exercises' => [
                    self::pairs('Match the business phrases', 'Match each English phrase to its Bulgarian equivalent.', [
                        ['To reach an agreement', 'постигам споразумение'],
                        ['To make a concession', 'правя отстъпка'],
                        ['To meet a deadline', 'спазвам срок'],
                        ['To sign a contract', 'подписвам договор'],
                        ['To submit an offer', 'подавам оферта'],
                    ]),
                    self::fill('Reached an agreement', 'Страните най-накрая __ споразумение.', ['направиха', 'постигнаха', 'стигнаха'], 1,
                        '"Страните най-накрая постигнаха споразумение." means "The parties finally reached an agreement." The collocation is "постигам споразумение".'),
                    self::trueFalse('True or false: Bihme mogli', '"Бихме могли да обсъдим отстъпка" is a softened way to suggest a discount.', true,
                        'Correct. "Бихме могли" (we could) sounds less direct than "искаме".'),
                    self::fill('Enters into force', 'Договорът влиза в __ от първи януари.', ['мощ', 'срок', 'сила'], 2,
                        '"Договорът влиза в сила от първи януари." means "The contract enters into force on 1 January."'),
                    self::trueFalse('True or false: Spazvam sroka', '"Спазвам срока" means "to miss the deadline".', false,
                        'It means "to meet the deadline". Missing it is "пропускам срока" or "не спазвам срока".'),
                    self::pairs('Match the contract words', 'Match each English term to its Bulgarian equivalent.', [
                        ['Counter-offer', 'насрещно предложение'],
                        ['Terms and conditions', 'общи условия'],
                        ['Penalty', 'неустойка'],
                        ['Invoice', 'фактура'],
                        ['Tender', 'търг'],
                    ]),
                    self::fill('A penalty for late payment', 'При забава на плащането се дължи __ в размер на 1% на ден.', ['отстъпка', 'фактура', 'неустойка'], 2,
                        '"При забава на плащането се дължи неустойка..." means "Late payment incurs a penalty of 1% per day."'),
                ],
            ],
            [
                'name' => 'Science and Discovery',
                'description' => 'Academic register: the passive, impersonal "се" and scholarly collocations.',
                'exercises' => [
                    self::pairs('Match the research words', 'Match each English word to its Bulgarian equivalent.', [
                        ['Hypothesis', 'хипотеза'],
                        ['Evidence', 'доказателства'],
                        ['Research', 'изследване'],
                        ['Findings', 'резултати'],
                        ['Peer review', 'рецензиране'],
                    ]),
                    self::fill('The hypothesis was confirmed', 'Хипотезата беше __ от последващите експерименти.', ['потвърдила', 'потвърдена', 'потвърждаваща'], 1,
                        '"Хипотезата беше потвърдена от последващите експерименти." means "The hypothesis was confirmed by later experiments."'),
                    self::trueFalse('True or false: Impersonal se', 'Bulgarian academic writing often uses impersonal forms such as "Установено е, че..." and "Смята се, че..."', true,
                        'Correct. They mean "It has been established that..." and "It is believed that...".'),
                    self::fill('The data suggest', 'Данните __ на извода, че температурите растат.', ['водят', 'дават', 'навеждат'], 2,
                        '"Данните навеждат на извода, че температурите растат." means "The data suggest that temperatures are rising." The collocation is "навеждам на извода".'),
                    self::trueFalse('True or false: Izsledvane', '"Изследване" can only mean a medical test, never scientific research.', false,
                        'It means both: a blood test (изследване на кръв) and a scientific study.'),
                    self::pairs('Match the research verbs', 'Match each English verb to its Bulgarian equivalent.', [
                        ['To prove', 'доказвам'],
                        ['To refute', 'опровергавам'],
                        ['To assume', 'предполагам'],
                        ['To measure', 'измервам'],
                        ['To observe', 'наблюдавам'],
                    ]),
                    self::fill('The results refuted it', 'Резултатите __ първоначалната хипотеза и учените трябваше да започнат отначало.', ['потвърдиха', 'опровергаха', 'доказаха'], 1,
                        '"Резултатите опровергаха първоначалната хипотеза..." means "The results refuted the original hypothesis, and the scientists had to start again."'),
                ],
            ],
            [
                'name' => 'Weather and Mood',
                'description' => 'Idioms that use weather to describe feelings and events.',
                'exercises' => [
                    self::pairs('Match the weather idioms', 'Match each English meaning to its Bulgarian idiom.', [
                        ['To be on cloud nine', 'на седмото небе съм'],
                        ['A storm in a teacup', 'буря в чаша вода'],
                        ['Out of the blue', 'като гръм от ясно небе'],
                        ['Too late to be useful', 'след дъжд качулка'],
                        ['To have your head in the clouds', 'витая в облаците'],
                    ]),
                    self::fill('Out of a clear sky', 'Новината дойде като гръм от __ небе.', ['синьо', 'ясно', 'облачно'], 1,
                        '"Новината дойде като гръм от ясно небе." means "The news came out of the blue", literally "like thunder from a clear sky".'),
                    self::trueFalse('True or false: Sled dazhd kachulka', '"След дъжд качулка" means "every cloud has a silver lining".', false,
                        'It means help or action that comes too late, literally "a hood after the rain".'),
                    self::fill('A storm in a teacup', 'Спорът им беше буря в __ вода.', ['кофа', 'море', 'чаша'], 2,
                        '"Спорът им беше буря в чаша вода." means "Their argument was a storm in a teacup."'),
                    self::trueFalse('True or false: Vitaya v oblatsite', '"Витая в облаците" describes someone dreamy and distracted.', true,
                        'Correct. Literally, "I hover in the clouds".'),
                    self::pairs('Match the moods', 'Match each English adjective to its Bulgarian equivalent.', [
                        ['Gloomy', 'мрачен'],
                        ['Cheerful', 'весел'],
                        ['Irritable', 'раздразнителен'],
                        ['Melancholic', 'меланхоличен'],
                        ['Carefree', 'безгрижен'],
                    ]),
                    self::fill('Rain makes him gloomy', 'Когато вали, той става __ и не му се излиза.', ['весел', 'мрачен', 'безгрижен'], 1,
                        '"Когато вали, той става мрачен и не му се излиза." means "When it rains he turns gloomy and does not feel like going out."'),
                ],
            ],
            [
                'name' => 'Traditions Today',
                'description' => 'Folk costume, ritual and dance, and the beliefs behind them.',
                'exercises' => [
                    self::pairs('Match the folklore words', 'Match each English phrase to its Bulgarian equivalent.', [
                        ['Folk song', 'народна песен'],
                        ['Traditional costume', 'народна носия'],
                        ['Ritual bread', 'обреден хляб'],
                        ['Round dance', 'хоро'],
                        ['Embroidery', 'шевица'],
                    ]),
                    self::fill('Whoever hangs the martenitsa', 'Според поверието, който __ мартеницата си на цъфнало дърво, ще бъде здрав цяла година.', ['закача', 'закачи', 'закачил'], 1,
                        '"...който закачи мартеницата си на цъфнало дърво, ще бъде здрав цяла година" means "...whoever hangs their martenitsa on a tree in blossom will be healthy all year."'),
                    self::trueFalse('True or false: Lazaruvane', '"Лазаруване" is a spring custom in which young girls sing and dance from house to house.', true,
                        'Correct. It takes place on Лазаровден, the Saturday before Palm Sunday.'),
                    self::fill('Playing the horo', 'Хорото се __ в кръг, като танцьорите се държат за ръце.', ['пее', 'върти', 'играе'], 2,
                        '"Хорото се играе в кръг..." means "The horo is danced in a circle..." Bulgarians "play" (играят) a horo rather than dance it.'),
                    self::trueFalse('True or false: Survakane', '"Сурвакане" takes place on Easter morning.', false,
                        'Сурвакане is on New Year\'s Day. Children tap people on the back with a decorated cornel branch (сурвачка) and wish them health.'),
                    self::pairs('Match the feast days', 'Match each English name to the Bulgarian feast day.', [
                        ['Lazarus\' Saturday', 'Лазаровден'],
                        ['Palm Sunday', 'Цветница'],
                        ['Midsummer Day (24 June)', 'Еньовден'],
                        ['St Nicholas\' Day', 'Никулден'],
                        ['St Tryphon\'s Day', 'Трифон Зарезан'],
                    ]),
                    self::fill('Herbs picked before sunrise', 'На Еньовден по традиция се __ билки преди изгрев.', ['бере', 'берат', 'брали'], 1,
                        '"На Еньовден по традиция се берат билки преди изгрев." means "On Midsummer Day herbs are traditionally picked before sunrise." Билки is plural.'),
                ],
            ],
        ];
    }
}
