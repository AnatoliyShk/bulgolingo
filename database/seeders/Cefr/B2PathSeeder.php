<?php

namespace Database\Seeders\Cefr;

use App\Enums\LanguageLevel;

class B2PathSeeder extends CefrPathSeeder
{
    protected function level(): LanguageLevel
    {
        return LanguageLevel::B2;
    }

    protected function pathName(): string
    {
        return 'Confident Bulgarian';
    }

    protected function lessons(): array
    {
        return [
            [
                'name' => 'First Impressions',
                'description' => 'Idioms for describing people, and the present perfect for experiences.',
                'exercises' => [
                    self::pairs('Match the character idioms', 'Match each English meaning to its Bulgarian idiom.', [
                        ['To hold a grudge', 'имам зъб на някого'],
                        ['To talk in vain', 'говоря на вятъра'],
                        ['To be good with your hands', 'имам златни ръце'],
                        ['To show off to impress', 'хвърлям прах в очите'],
                        ['To keep your word', 'държа на думата си'],
                    ]),
                    self::fill('A good impression', 'Той ми направи добро __ още на първата среща.', ['мнение', 'впечатление', 'изражение'], 1,
                        '"Той ми направи добро впечатление още на първата среща." means "He made a good impression on me at our very first meeting." The fixed phrase is "правя впечатление".'),
                    self::trueFalse('True or false: Imam zab', '"Имам зъб на някого" means "I have a toothache because of someone".', false,
                        'It means "I hold a grudge against someone".'),
                    self::fill('I have never met', 'Никога не съм __ толкова приятелски настроен човек.', ['срещнах', 'срещам', 'срещал'], 2,
                        '"Никога не съм срещал толкова приятелски настроен човек." means "I have never met such a friendly person." The present perfect is съм + the -л participle.'),
                    self::trueFalse('True or false: Present perfect', 'The present perfect ("съм срещал") is made from "съм" plus the past active participle.', true,
                        'Correct. The participle agrees with the subject: срещал, срещала, срещали.'),
                    self::pairs('Match the character adjectives', 'Match each English adjective to its Bulgarian equivalent.', [
                        ['Generous', 'щедър'],
                        ['Stingy', 'стиснат'],
                        ['Modest', 'скромен'],
                        ['Arrogant', 'надменен'],
                        ['Reliable', 'надежден'],
                    ]),
                    self::fill('A person who helps', 'Тя е човек, __ винаги помага на другите.', ['която', 'който', 'което'], 1,
                        '"Тя е човек, който винаги помага на другите." means "She is a person who always helps others." The relative pronoun agrees with човек, which is masculine.'),
                ],
            ],
            [
                'name' => 'Biographies',
                'description' => 'The renarrative mood: telling what you heard rather than what you saw.',
                'exercises' => [
                    self::pairs('Match witnessed and reported', 'Match each English description to the Bulgarian verb form.', [
                        ['He was (I saw it)', 'той беше'],
                        ['He was (I was told)', 'той бил'],
                        ['She lived (I saw it)', 'тя живееше'],
                        ['She lived (I was told)', 'тя живяла'],
                        ['They left (I was told)', 'те заминали'],
                    ]),
                    self::fill('I was not at the concert', 'Не бях на концерта, но той __ страхотен.', ['беше', 'бил', 'е'], 1,
                        '"Не бях на концерта, но той бил страхотен." means "I was not at the concert, but apparently it was great." You did not witness it, so the renarrative бил fits.'),
                    self::trueFalse('True or false: Renarrative mood', 'Bulgarian has special verb forms for events the speaker did not witness.', true,
                        'Correct. The renarrative mood (преизказно наклонение) marks information as reported, not seen.'),
                    self::fill('Once upon a time', 'Имало едно време един цар, който __ три дъщери.', ['имаше', 'има', 'имал'], 2,
                        '"Имало едно време един цар, който имал три дъщери." means "Once upon a time there was a king who had three daughters." Fairy tales are told in the renarrative, and the story keeps to it.'),
                    self::trueFalse('True or false: Zavarshil', '"Завършил е медицина." means he dropped out of medical school.', false,
                        'It means he graduated in medicine. Завършвам means "to finish" or "to graduate".'),
                    self::pairs('Match the reported be', 'Match each English phrase to the renarrative form of "съм".', [
                        ['I was (reportedly)', 'съм бил'],
                        ['You were (reportedly)', 'си бил'],
                        ['She was (reportedly)', 'била'],
                        ['We were (reportedly)', 'сме били'],
                        ['They were (reportedly)', 'били'],
                    ]),
                    self::fill('He has lived in Sofia', 'Той е роден в Габрово, но е __ целия си живот в София.', ['живя', 'живееше', 'живял'], 2,
                        '"Той е роден в Габрово, но е живял целия си живот в София." means "He was born in Gabrovo but has lived all his life in Sofia." After е you need the -л participle.'),
                ],
            ],
            [
                'name' => 'Generations',
                'description' => 'Family across generations, the pluperfect and unreal conditions.',
                'exercises' => [
                    self::pairs('Match the generation words', 'Match each English phrase to its Bulgarian equivalent.', [
                        ['Generation', 'поколение'],
                        ['Upbringing', 'възпитание'],
                        ['Inheritance', 'наследство'],
                        ['To rely on', 'разчитам на'],
                        ['To take after (resemble)', 'приличам на'],
                    ]),
                    self::fill('She had already cooked', 'Когато пристигнахме, баба вече __ обяда.', ['сготви', 'беше сготвила', 'готви'], 1,
                        '"Когато пристигнахме, баба вече беше сготвила обяда." means "When we arrived, grandma had already cooked lunch." The earlier of two past events takes the pluperfect.'),
                    self::trueFalse('True or false: Ako byah znael', '"Ако бях знаел, щях да дойда." means "If I had known, I would have come."', true,
                        'Correct. The pluperfect after ако plus щях да makes a past unreal condition.'),
                    self::fill('I would call more often', 'Ако имах повече време, __ по-често на родителите си.', ['ще се обадя', 'се обадих', 'бих се обаждал'], 2,
                        '"Ако имах повече време, бих се обаждал по-често на родителите си." means "If I had more time, I would call my parents more often."'),
                    self::trueFalse('True or false: Prilicham', '"Приличам на баща си." means "I like my father."', false,
                        'It means "I look like my father". "Харесвам" is "to like".'),
                    self::pairs('Match the values', 'Match each English word to its Bulgarian equivalent.', [
                        ['Tradition', 'традиция'],
                        ['Progress', 'прогрес'],
                        ['Respect', 'уважение'],
                        ['Conflict', 'конфликт'],
                        ['Independence', 'независимост'],
                    ]),
                    self::fill('If I were younger', 'Ако __ по-млад, бих учил отново.', ['съм', 'бях', 'бъда'], 1,
                        '"Ако бях по-млад, бих учил отново." means "If I were younger, I would study again." An unreal condition takes the past бях.'),
                ],
            ],
            [
                'name' => 'Busy Lives',
                'description' => 'Time management, verbal nouns in -не and pairs of imperfective and perfective verbs.',
                'exercises' => [
                    self::pairs('Match the aspect pairs', 'Match each imperfective verb with its perfective partner.', [
                        ['казвам', 'кажа'],
                        ['давам', 'дам'],
                        ['купувам', 'купя'],
                        ['отговарям', 'отговоря'],
                        ['започвам', 'започна'],
                    ]),
                    self::fill('Ordering tasks', '__ на задачите по важност спестява време.', ['Подреждам', 'Подреждането', 'Подредих'], 1,
                        '"Подреждането на задачите по важност спестява време." means "Ordering tasks by importance saves time." The verbal noun подреждане acts as the subject.'),
                    self::trueFalse('True or false: Otlagam', '"Отлагам" means "to put off" or "to postpone".', true,
                        'Correct. "Не отлагай за утре това, което можеш да направиш днес."'),
                    self::fill('All morning', 'Цяла сутрин __ на имейли и не успях да свърша нищо друго.', ['отговорих', 'отговарям', 'отговарях'], 2,
                        '"Цяла сутрин отговарях на имейли..." means "All morning I was answering emails..." An activity over a stretch of time takes the imperfective.'),
                    self::trueFalse('True or false: Verbal nouns', 'Verbal nouns ending in -не, like "четене", are feminine.', false,
                        'They are neuter: четенето, писането, подреждането.'),
                    self::pairs('Match the productivity words', 'Match each English phrase to its Bulgarian equivalent.', [
                        ['To set priorities', 'определям приоритети'],
                        ['To delegate', 'делегирам'],
                        ['To multitask', 'върша няколко неща едновременно'],
                        ['Burnout', 'прегаряне'],
                        ['Work-life balance', 'баланс между работата и личния живот'],
                    ]),
                    self::fill('I read three chapters', 'Докато чаках влака, __ три глави от книгата.', ['прочетох', 'четях', 'чета'], 0,
                        '"Докато чаках влака, прочетох три глави от книгата." means "While I was waiting for the train, I read three chapters." A finished, counted result takes the perfective.'),
                ],
            ],
            [
                'name' => 'The Housing Market',
                'description' => 'Buying property, mortgages and past passive participles.',
                'exercises' => [
                    self::pairs('Match the property words', 'Match each English phrase to its Bulgarian equivalent.', [
                        ['Mortgage', 'ипотечен кредит'],
                        ['Estate agency', 'агенция за недвижими имоти'],
                        ['Down payment', 'самоучастие'],
                        ['Square metre', 'квадратен метър'],
                        ['Newly built', 'новопостроен'],
                    ]),
                    self::fill('Fully furnished', 'Апартаментът е напълно __ и готов за нанасяне.', ['обзавел', 'обзаведен', 'обзавеждан'], 1,
                        '"Апартаментът е напълно обзаведен и готов за нанасяне." means "The flat is fully furnished and ready to move into." Обзаведен is a past passive participle.'),
                    self::trueFalse('True or false: Postroena', '"Сградата е построена през 2010 г." means "The building was built in 2010."', true,
                        'Correct. Съм + a past passive participle makes the passive.'),
                    self::fill('VAT included', 'Цената __ ДДС.', ['включена', 'включи', 'включва'], 2,
                        '"Цената включва ДДС." means "The price includes VAT."'),
                    self::trueFalse('True or false: Tuhlena kooperatsiya', '"Тухлена кооперация" is a wooden house in a village.', false,
                        'It is a brick-built block of flats, usually older and smaller than the prefabricated panel blocks.'),
                    self::pairs('Match the moving verbs', 'Match each English phrase to its Bulgarian equivalent.', [
                        ['To move in', 'нанасям се'],
                        ['To move out', 'изнасям се'],
                        ['To renovate', 'ремонтирам'],
                        ['To insulate (a building)', 'санирам'],
                        ['Neighbourhood', 'квартал'],
                    ]),
                    self::fill('The building was insulated', 'Сградата беше __ миналата година и сега е по-топла.', ['санирала', 'санираща', 'санирана'], 2,
                        '"Сградата беше санирана миналата година..." means "The building was insulated last year and is now warmer." The passive needs the past passive participle.'),
                ],
            ],
            [
                'name' => 'Hospitality and Etiquette',
                'description' => 'Negative questions as invitations, negative commands and everyday reactions.',
                'exercises' => [
                    self::pairs('Match the reactions', 'Match each English reaction to its Bulgarian equivalent.', [
                        ['Come on!', 'Хайде!'],
                        ['Really?', 'Така ли?'],
                        ['No way!', 'Няма начин!'],
                        ['Never mind', 'Няма значение'],
                        ['Of course', 'Разбира се'],
                    ]),
                    self::fill('Stay a bit longer', 'Няма ли да __ още малко? Рано е!', ['остави', 'останеш', 'остана'], 1,
                        '"Няма ли да останеш още малко? Рано е!" means "Won\'t you stay a little longer? It\'s early!" A negative question makes a warm invitation.'),
                    self::trueFalse('True or false: Nodding', 'Traditionally in Bulgaria, nodding the head up and down means "no", and shaking it means "yes".', true,
                        'Correct. Many younger Bulgarians use both gestures, so listen for "да" and "не" as well.'),
                    self::fill('Do not worry about the dishes', 'Не се __ за чиниите, ние ще ги измием.', ['безпокоите', 'безпокоихте', 'безпокойте'], 2,
                        '"Не се безпокойте за чиниите, ние ще ги измием." means "Don\'t worry about the dishes, we will wash them." A negative command takes the imperfective.'),
                    self::trueFalse('True or false: Negative imperative', 'A negative command usually takes the perfective aspect: "Не затвори вратата!"', false,
                        'A negative command normally takes the imperfective: "Не затваряй вратата!"'),
                    self::pairs('Match the exclamations', 'Match each English exclamation to its Bulgarian equivalent.', [
                        ['Wow!', 'Леле!'],
                        ['Oh dear!', 'Олеле!'],
                        ['Well done!', 'Браво!'],
                        ['Well then', 'Е, добре'],
                        ['Enough!', 'Стига!'],
                    ]),
                    self::fill('Do not be shy', 'Не се __ и се чувствайте като у дома си!', ['стеснявате', 'стеснявайте', 'стеснете'], 1,
                        '"Не се стеснявайте и се чувствайте като у дома си!" means "Don\'t be shy, make yourselves at home!" A negative command takes the imperfective.'),
                ],
            ],
            [
                'name' => 'Workplace Communication',
                'description' => 'Formal letters, set phrases and the passive with "бъда".',
                'exercises' => [
                    self::pairs('Match the letter phrases', 'Match each English phrase to its Bulgarian equivalent.', [
                        ['Dear Sir or Madam', 'Уважаеми госпожи и господа'],
                        ['Kind regards', 'С уважение'],
                        ['Please find attached', 'Приложено Ви изпращам'],
                        ['I look forward to your reply', 'Очаквам отговора Ви'],
                        ['Regarding', 'Относно'],
                    ]),
                    self::fill('Thank you for the reply', '__ Ви за бързия отговор.', ['Благодарение', 'Благодаря', 'Благодарен'], 1,
                        '"Благодаря Ви за бързия отговор." means "Thank you for the quick reply."'),
                    self::trueFalse('True or false: Capital Vie', 'In a formal Bulgarian letter to one person, "Вие" and "Ви" are written with a capital letter.', true,
                        'Correct. The capital letter shows respect to the reader.'),
                    self::fill('Must be finished', 'Проектът трябва да бъде __ до края на месеца.', ['завършващ', 'завършил', 'завършен'], 2,
                        '"Проектът трябва да бъде завършен до края на месеца." means "The project must be finished by the end of the month."'),
                    self::trueFalse('True or false: Komandirovka', '"Командировка" means "a promotion".', false,
                        'A командировка is a business trip. A promotion is "повишение".'),
                    self::pairs('Match the email terms', 'Match each English term to its Bulgarian equivalent.', [
                        ['Attachment', 'прикачен файл'],
                        ['Recipient', 'получател'],
                        ['Sender', 'подател'],
                        ['Signature', 'подпис'],
                        ['Reply all', 'отговор до всички'],
                    ]),
                    self::fill('Please send the documents', 'Моля, __ ни необходимите документи до петък.', ['изпращайте', 'изпратихте', 'изпратете'], 2,
                        '"Моля, изпратете ни необходимите документи до петък." means "Please send us the necessary documents by Friday." A single request takes the perfective command.'),
                ],
            ],
            [
                'name' => 'Environment and Science',
                'description' => 'Climate and pollution, and abstract nouns built with -ост and -ство.',
                'exercises' => [
                    self::pairs('Match the environment words', 'Match each English phrase to its Bulgarian equivalent.', [
                        ['Climate change', 'климатични промени'],
                        ['Pollution', 'замърсяване'],
                        ['Renewable energy', 'възобновяема енергия'],
                        ['Drought', 'суша'],
                        ['Recycling', 'рециклиране'],
                    ]),
                    self::fill('Sorting waste', 'Все повече хора __ отпадъците си.', ['разделя', 'разделят', 'разделихме'], 1,
                        '"Все повече хора разделят отпадъците си." means "More and more people sort their waste." Хора is plural.'),
                    self::trueFalse('True or false: Suffix -ost', 'The suffix -ост makes abstract nouns from adjectives: "устойчив" becomes "устойчивост".', true,
                        'Correct. Устойчив means "sustainable", so устойчивост is "sustainability".'),
                    self::fill('Cut emissions in half', 'Ако до 2030 г. не __ емисиите наполовина, климатът ще продължи да се затопля.', ['намаляваме', 'намалихме', 'намалим'], 2,
                        '"Ако до 2030 г. не намалим емисиите наполовина..." means "If we do not cut emissions in half by 2030..." A single result by a deadline takes the perfective.'),
                    self::trueFalse('True or false: Susha', '"Суша" means "flood".', false,
                        '"Суша" means "drought", and also "dry land". A flood is "наводнение".'),
                    self::pairs('Match the environment verbs', 'Match each English verb to its Bulgarian equivalent.', [
                        ['To pollute', 'замърсявам'],
                        ['To protect', 'опазвам'],
                        ['To save (energy)', 'пестя'],
                        ['To waste', 'хабя'],
                        ['To reduce', 'намалявам'],
                    ]),
                    self::fill('Forests must be protected', 'Горите трябва да бъдат __ от пожари.', ['опазващи', 'опазени', 'опазили'], 1,
                        '"Горите трябва да бъдат опазени от пожари." means "Forests must be protected from fires."'),
                ],
            ],
            [
                'name' => 'Climate and Seasons',
                'description' => 'Predictions, hedging and the future in the past with "щеше да".',
                'exercises' => [
                    self::pairs('Match the hedges', 'Match each English phrase to its Bulgarian equivalent.', [
                        ['It is likely', 'вероятно е'],
                        ['It is expected', 'очаква се'],
                        ['Supposedly', 'уж'],
                        ['Without a doubt', 'без съмнение'],
                        ['Hardly', 'едва ли'],
                    ]),
                    self::fill('It was going to rain', 'Синоптиците казаха, че __ да вали, но беше слънчево цял ден.', ['ще', 'щеше', 'щял'], 1,
                        '"Синоптиците казаха, че щеше да вали..." means "The forecasters said it was going to rain..." A future seen from the past takes щеше да.'),
                    self::trueFalse('True or false: Edva li', '"Едва ли ще завали." means "It will definitely rain."', false,
                        'It means "It is unlikely to rain". Едва ли expresses doubt.'),
                    self::fill('Raining heavily', 'Вали като из __ цял ден.', ['чаша', 'небе', 'ведро'], 2,
                        '"Вали като из ведро цял ден." means "It has been pouring all day", literally "raining as if from a bucket".'),
                    self::trueFalse('True or false: Uzh', '"Уж е лято, а е студено." means "It\'s supposedly summer, but it\'s cold."', true,
                        'Correct. Уж casts doubt on what follows.'),
                    self::pairs('Match the extreme weather', 'Match each English word to its Bulgarian equivalent.', [
                        ['Hail', 'градушка'],
                        ['Flood', 'наводнение'],
                        ['Blizzard', 'виелица'],
                        ['Landslide', 'свлачище'],
                        ['Thunderstorm', 'гръмотевична буря'],
                    ]),
                    self::fill('We would have gone out', 'Ако не беше валяло, __ на разходка.', ['ще излезем', 'излязохме', 'щяхме да излезем'], 2,
                        '"Ако не беше валяло, щяхме да излезем на разходка." means "If it had not rained, we would have gone for a walk."'),
                ],
            ],
            [
                'name' => 'Heritage and Holidays',
                'description' => 'National holidays, cultural heritage and the passive voice.',
                'exercises' => [
                    self::pairs('Match the national holidays', 'Match each date to the Bulgarian name of the holiday.', [
                        ['3 March', 'Ден на Освобождението'],
                        ['24 May', 'Ден на българската просвета и култура'],
                        ['6 September', 'Ден на Съединението'],
                        ['22 September', 'Ден на Независимостта'],
                        ['1 November', 'Ден на народните будители'],
                    ]),
                    self::fill('Cyrillic was created', 'Кирилицата е __ в края на IX век.', ['създала', 'създадена', 'създаваща'], 1,
                        '"Кирилицата е създадена в края на IX век." means "The Cyrillic alphabet was created at the end of the 9th century."'),
                    self::trueFalse('True or false: 3 March', 'On 3 March Bulgaria celebrates its liberation from Ottoman rule in 1878.', true,
                        'Correct. It is the national day, marking the Treaty of San Stefano.'),
                    self::fill('On the UNESCO list', 'Рилският манастир е __ в списъка на ЮНЕСКО.', ['включил', 'включващ', 'включен'], 2,
                        '"Рилският манастир е включен в списъка на ЮНЕСКО." means "The Rila Monastery is included in the UNESCO list."'),
                    self::trueFalse('True or false: Unification Day', 'Unification Day on 6 September marks the union of Bulgaria with Macedonia.', false,
                        'It marks the union of the Principality of Bulgaria with Eastern Rumelia in 1885.'),
                    self::pairs('Match the heritage sites', 'Match each English term to its Bulgarian equivalent.', [
                        ['Thracian tomb', 'тракийска гробница'],
                        ['Fortress', 'крепост'],
                        ['Monastery', 'манастир'],
                        ['Icon', 'икона'],
                        ['Mural', 'стенопис'],
                    ]),
                    self::fill('Famous for its murals', 'Боянската църква е известна със __ си от XIII век.', ['стенописи', 'стенописите', 'стенопис'], 1,
                        '"Боянската църква е известна със стенописите си от XIII век." means "Boyana Church is famous for its 13th-century murals." With си the noun takes the article.'),
                ],
            ],
        ];
    }
}
