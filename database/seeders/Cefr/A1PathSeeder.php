<?php

namespace Database\Seeders\Cefr;

use App\Enums\LanguageLevel;

class A1PathSeeder extends CefrPathSeeder
{
    protected function level(): LanguageLevel
    {
        return LanguageLevel::A1;
    }

    protected function pathName(): string
    {
        return 'First Steps in Bulgarian';
    }

    protected function lessons(): array
    {
        return [
            [
                'name' => 'Hello, Nice to Meet You',
                'description' => 'Greet people, ask how they are and use the verb "съм" (to be).',
                'exercises' => [
                    self::pairs('Match the first phrases', 'Match each English phrase to its Bulgarian equivalent.', [
                        ['Hi', 'Здрасти'],
                        ['Good day', 'Добър ден'],
                        ['How are you? (polite)', 'Как сте?'],
                        ['Nice to meet you', 'Приятно ми е'],
                        ['See you tomorrow', 'До утре'],
                    ]),
                    self::fill('I am Maria', 'Аз __ Мария.', ['си', 'съм', 'е'], 1,
                        '"Аз съм Мария." means "I am Maria." Съм is the "I" form of the verb "to be".'),
                    self::trueFalse('True or false: Kak ste', '"Как сте?" is the polite way to ask a stranger "How are you?"', true,
                        'Correct. "Сте" is the plural form, which Bulgarian also uses to be polite. With a friend you say "Как си?".'),
                    self::fill('We are from Plovdiv', 'Ние __ от Пловдив.', ['са', 'сте', 'сме'], 2,
                        '"Ние сме от Пловдив." means "We are from Plovdiv." Сме goes with "ние" (we).'),
                    self::trueFalse('True or false: Te', '"Те" means "you" when you talk to several people.', false,
                        '"Те" means "they". When you talk to several people, "you" is "вие".'),
                    self::pairs('Match the pronouns', 'Match each English pronoun to its Bulgarian equivalent.', [
                        ['I', 'аз'],
                        ['You (one friend)', 'ти'],
                        ['He', 'той'],
                        ['She', 'тя'],
                        ['We', 'ние'],
                    ]),
                    self::fill('They are students', 'Те __ студенти.', ['сте', 'са', 'сме'], 1,
                        '"Те са студенти." means "They are students." Са goes with "те" (they).'),
                ],
            ],
            [
                'name' => 'Who Are You?',
                'description' => 'Say where you are from and what you do, with masculine and feminine nouns for people.',
                'exercises' => [
                    self::pairs('Match the people', 'Match each English word to its Bulgarian equivalent.', [
                        ['Bulgarian (man)', 'българин'],
                        ['Bulgarian (woman)', 'българка'],
                        ['Teacher (man)', 'учител'],
                        ['Teacher (woman)', 'учителка'],
                        ['Student (woman)', 'студентка'],
                    ]),
                    self::fill('Jessica from London', 'Джесика е __ от Лондон.', ['англичанин', 'англичанка', 'англичани'], 1,
                        '"Джесика е англичанка от Лондон." means "Jessica is an Englishwoman from London." Jessica is a woman, so the feminine form is needed.'),
                    self::trueFalse('True or false: the -ка ending', 'Many Bulgarian words for women end in -ка, like "лекарка" (female doctor).', true,
                        'Correct. Adding -ка to a masculine noun often gives the feminine one: лекар, лекарка; учител, учителка.'),
                    self::fill('He is a doctor', 'Той е __ и работи в болница.', ['лекар', 'лекарка', 'лекари'], 0,
                        '"Той е лекар и работи в болница." means "He is a doctor and works in a hospital." Той (he) takes the masculine form.'),
                    self::trueFalse('True or false: Na kolko godini si', '"На колко години си?" asks "Where are you from?"', false,
                        '"На колко години си?" asks "How old are you?". "Where are you from?" is "Откъде си?".'),
                    self::pairs('Match the countries', 'Match each country to its Bulgarian name.', [
                        ['England', 'Англия'],
                        ['Germany', 'Германия'],
                        ['France', 'Франция'],
                        ['Italy', 'Италия'],
                        ['Greece', 'Гърция'],
                    ]),
                    self::fill('Twenty years old', 'Аз съм на двадесет __ и живея в Русе.', ['години', 'година', 'годишен'], 0,
                        '"Аз съм на двадесет години и живея в Русе." means "I am twenty years old and I live in Ruse." Age is given with "на ... години".'),
                ],
            ],
            [
                'name' => 'My Family',
                'description' => 'Name your relatives and talk about what you have with "имам" and "има".',
                'exercises' => [
                    self::pairs('Match the family', 'Match each family member to its Bulgarian word.', [
                        ['Mother', 'майка'],
                        ['Father', 'баща'],
                        ['Brother', 'брат'],
                        ['Sister', 'сестра'],
                        ['Grandmother', 'баба'],
                    ]),
                    self::fill('One brother and one sister', 'Аз __ един брат и една сестра.', ['има', 'имаш', 'имам'], 2,
                        '"Аз имам един брат и една сестра." means "I have one brother and one sister."'),
                    self::trueFalse('True or false: Nyamam', 'The opposite of "имам" (I have) is "нямам" (I do not have).', true,
                        'Correct. Bulgarian never says "не имам". The negative is one word: нямам, нямаш, няма.'),
                    self::fill('This is my sister', 'Това е __ сестра.', ['моят', 'моята', 'моето'], 1,
                        '"Това е моята сестра." means "This is my sister." Сестра is feminine, so "my" is моята. You can also say "сестра ми".'),
                    self::trueFalse('True or false: Ima', '"В стаята има котка." means "There is a cat in the room."', true,
                        'Correct. "Има" means "there is" or "there are". Its negative is "няма".'),
                    self::pairs('Match more family', 'Match each family word to its Bulgarian equivalent.', [
                        ['Son', 'син'],
                        ['Daughter', 'дъщеря'],
                        ['Grandfather', 'дядо'],
                        ['Child', 'дете'],
                        ['Parents', 'родители'],
                    ]),
                    self::fill('Two sisters', 'Нямам брат, но __ две сестри.', ['нямам', 'има', 'имам'], 2,
                        '"Нямам брат, но имам две сестри." means "I do not have a brother, but I have two sisters."'),
                ],
            ],
            [
                'name' => 'Numbers and My Day',
                'description' => 'Count to twenty, name the days of the week and describe your day in the present tense.',
                'exercises' => [
                    self::pairs('Match the weekdays', 'Match each day of the week to its Bulgarian name.', [
                        ['Monday', 'понеделник'],
                        ['Tuesday', 'вторник'],
                        ['Wednesday', 'сряда'],
                        ['Thursday', 'четвъртък'],
                        ['Friday', 'петък'],
                    ]),
                    self::fill('I get up at eight', 'Всеки ден аз __ в осем часа.', ['ставам', 'ставаш', 'става'], 0,
                        '"Всеки ден аз ставам в осем часа." means "Every day I get up at eight o\'clock."'),
                    self::trueFalse('True or false: Dvanadeset', '"Дванадесет" is the number 12.', true,
                        'Correct. Дванадесет is 12. In speech it is often shortened to "дванайсет".'),
                    self::fill('We sleep until noon', 'В събота ние __ до обяд.', ['спя', 'спят', 'спим'], 2,
                        '"В събота ние спим до обяд." means "On Saturday we sleep until noon." Спим goes with "ние".'),
                    self::trueFalse('True or false: Nedelya', '"Неделя" is Saturday.', false,
                        '"Неделя" is Sunday. Saturday is "събота".'),
                    self::pairs('Match the numbers', 'Match each number to its Bulgarian word.', [
                        ['3', 'три'],
                        ['7', 'седем'],
                        ['11', 'единадесет'],
                        ['15', 'петнадесет'],
                        ['20', 'двадесет'],
                    ]),
                    self::fill('Monday to Friday', 'Работя от понеделник до __ всяка седмица.', ['утре', 'петък', 'сутрин'], 1,
                        '"Работя от понеделник до петък всяка седмица." means "I work from Monday to Friday every week."'),
                ],
            ],
            [
                'name' => 'Our Home',
                'description' => 'Rooms, furniture and simple directions around the house.',
                'exercises' => [
                    self::pairs('Match the home words', 'Match each part of the home to its Bulgarian word.', [
                        ['Kitchen', 'кухня'],
                        ['Bedroom', 'спалня'],
                        ['Bathroom', 'баня'],
                        ['Window', 'прозорец'],
                        ['Door', 'врата'],
                    ]),
                    self::fill('Where the fridge is', 'Хладилникът е в __ до печката.', ['банята', 'кухнята', 'спалнята'], 1,
                        '"Хладилникът е в кухнята до печката." means "The fridge is in the kitchen next to the cooker."'),
                    self::trueFalse('True or false: Leglo', '"Легло" (bed) is a neuter noun.', true,
                        'Correct. Most nouns ending in -о or -е are neuter: легло, кресло, огледало.'),
                    self::fill('On the right', 'Шкафът не е вляво, а __ от вратата.', ['вкъщи', 'вляво', 'вдясно'], 2,
                        '"Шкафът не е вляво, а вдясно от вратата." means "The wardrobe is not on the left but to the right of the door."'),
                    self::trueFalse('True or false: Stai', 'The plural of "стая" (room) is "стаи".', true,
                        'Correct. Feminine nouns ending in -я usually change it to -и in the plural: стая, стаи.'),
                    self::pairs('Match the furniture', 'Match each piece of furniture to its Bulgarian word.', [
                        ['Table', 'маса'],
                        ['Chair', 'стол'],
                        ['Bed', 'легло'],
                        ['Wardrobe', 'гардероб'],
                        ['Sofa', 'диван'],
                    ]),
                    self::fill('A big sofa', 'В хола има голям __ и две кресла.', ['маса', 'легла', 'диван'], 2,
                        '"В хола има голям диван и две кресла." means "In the living room there is a big sofa and two armchairs." Голям is masculine, and so is диван.'),
                ],
            ],
            [
                'name' => 'Visiting Friends',
                'description' => 'Ask questions, offer drinks and use "искам" (to want) as a guest or host.',
                'exercises' => [
                    self::pairs('Match the question words', 'Match each English question word to its Bulgarian equivalent.', [
                        ['What?', 'Какво?'],
                        ['Where?', 'Къде?'],
                        ['When?', 'Кога?'],
                        ['Who?', 'Кой?'],
                        ['Why?', 'Защо?'],
                    ]),
                    self::fill('Tea or coffee', 'Какво __ да пиеш, чай или кафе?', ['искам', 'искаш', 'искат'], 1,
                        '"Какво искаш да пиеш, чай или кафе?" means "What do you want to drink, tea or coffee?" The verb matches "ти" (you), which is the subject of "пиеш".'),
                    self::trueFalse('True or false: Zapovyadaite', '"Заповядайте!" can be used to invite a guest to come in.', true,
                        'Correct. "Заповядайте!" means "Please come in" or "Here you are", depending on the moment.'),
                    self::fill('Just water', 'Благодаря, аз __ само вода.', ['искам', 'иска', 'искаш'], 0,
                        '"Благодаря, аз искам само вода." means "Thank you, I only want water."'),
                    self::trueFalse('True or false: Nie iskat', 'The "we" form of "искам" is "ние искат".', false,
                        'The "we" form is "ние искаме". "Искат" goes with "те" (they).'),
                    self::pairs('Match food and drink', 'Match each English word to its Bulgarian equivalent.', [
                        ['Water', 'вода'],
                        ['Wine', 'вино'],
                        ['Bread', 'хляб'],
                        ['Cheese', 'сирене'],
                        ['Cake', 'торта'],
                    ]),
                    self::fill('What is your name', '__ се казваш?', ['Какво', 'Как', 'Кой'], 1,
                        '"Как се казваш?" means "What is your name?", literally "How are you called?".'),
                ],
            ],
            [
                'name' => 'At Work',
                'description' => 'Jobs, simple commands and the words "който" and "която" (who).',
                'exercises' => [
                    self::pairs('Match the jobs', 'Match each job to its Bulgarian word.', [
                        ['Engineer', 'инженер'],
                        ['Driver', 'шофьор'],
                        ['Lawyer', 'адвокат'],
                        ['Waiter', 'сервитьор'],
                        ['Nurse', 'медицинска сестра'],
                    ]),
                    self::fill('Close the door', 'Моля, __ вратата!', ['затварям', 'затвори', 'затваря'], 1,
                        '"Моля, затвори вратата!" means "Please close the door!" Затвори is the command form.'),
                    self::trueFalse('True or false: Koyto', 'In "Колегата, който работи с мен, е от Варна", the word "който" means "who".', true,
                        'Correct. "Който" is "who" for a man. The sentence means "The colleague who works with me is from Varna."'),
                    self::fill('The woman who works here', 'Жената, __ работи тук, е моя колежка.', ['който', 'което', 'която'], 2,
                        '"Жената, която работи тук, е моя колежка." means "The woman who works here is my colleague." Жена is feminine, so "who" is която.'),
                    self::trueFalse('True or false: Chakai', 'The command "Чакай!" (wait!) is polite enough for your boss.', false,
                        '"Чакай!" is informal. To your boss say "Изчакайте, моля."'),
                    self::pairs('Match the workplaces', 'Match each workplace to its Bulgarian word.', [
                        ['Office', 'офис'],
                        ['Hospital', 'болница'],
                        ['School', 'училище'],
                        ['Shop', 'магазин'],
                        ['Factory', 'фабрика'],
                    ]),
                    self::fill('The teacher works at a school', 'Учителят работи в __ до парка.', ['училище', 'болница', 'фабрика'], 0,
                        '"Учителят работи в училище до парка." means "The teacher works at a school next to the park."'),
                ],
            ],
            [
                'name' => 'Our Planet',
                'description' => 'The sky, the Earth and comparing things with "по-" (more) and "най-" (most).',
                'exercises' => [
                    self::pairs('Match the sky words', 'Match each English word to its Bulgarian equivalent.', [
                        ['Sun', 'слънце'],
                        ['Moon', 'луна'],
                        ['Star', 'звезда'],
                        ['Sky', 'небе'],
                        ['Sea', 'море'],
                    ]),
                    self::fill('Bigger than the Moon', 'Слънцето е __ от Луната.', ['голям', 'по-голямо', 'най-голямо'], 1,
                        '"Слънцето е по-голямо от Луната." means "The Sun is bigger than the Moon." Use по- with "от" (than).'),
                    self::trueFalse('True or false: Nai-visokiyat', '"Най-високият" means "the tallest" or "the highest".', true,
                        'Correct. Най- makes the superlative: висок (tall), по-висок (taller), най-висок (tallest).'),
                    self::fill('Musala', 'Мусала е __ връх на Балканския полуостров.', ['високата', 'по-високият', 'най-високият'], 2,
                        '"Мусала е най-високият връх на Балканския полуостров." means "Musala is the highest peak in the Balkan Peninsula."'),
                    self::trueFalse('True or false: Po-malko', '"По-малко" means "the most".', false,
                        '"По-малко" means "less". "The most" is "най-много".'),
                    self::pairs('Match the opposites', 'Match each English adjective to its Bulgarian equivalent.', [
                        ['Big', 'голям'],
                        ['Small', 'малък'],
                        ['Hot', 'горещ'],
                        ['Cold', 'студен'],
                        ['Far', 'далечен'],
                    ]),
                    self::fill('Smaller than the Earth', 'Луната е __ от Земята.', ['по-голяма', 'най-малка', 'по-малка'], 2,
                        '"Луната е по-малка от Земята." means "The Moon is smaller than the Earth." Луна is feminine, so the adjective ends in -а.'),
                ],
            ],
            [
                'name' => 'What Time Is It?',
                'description' => 'Tell the time, talk about today and tomorrow, and say what you must do with "трябва".',
                'exercises' => [
                    self::pairs('Match the time words', 'Match each English word to its Bulgarian equivalent.', [
                        ['Today', 'днес'],
                        ['Tomorrow', 'утре'],
                        ['Yesterday', 'вчера'],
                        ['Now', 'сега'],
                        ['Later', 'по-късно'],
                    ]),
                    self::fill('Half past three', 'Сега е три __ и половина.', ['час', 'часа', 'часове'], 1,
                        '"Сега е три часа и половина." means "It is half past three." After a number, час becomes часа.'),
                    self::trueFalse('True or false: Vreme', 'The Bulgarian word "време" can mean both "time" and "weather".', true,
                        'Correct. "Нямам време" is "I have no time", and "Какво е времето?" is "What is the weather like?".'),
                    self::fill('I must get up early', 'Утре __ да ставам рано.', ['трябвам', 'трябваш', 'трябва'], 2,
                        '"Утре трябва да ставам рано." means "Tomorrow I must get up early." Трябва stays the same for every person.'),
                    self::trueFalse('True or false: Studeno', '"Днес е студено." means "It is hot today."', false,
                        '"Студено" means "cold". "It is hot" is "Горещо е".'),
                    self::pairs('Match the parts of the day', 'Match each part of the day to its Bulgarian word.', [
                        ['Morning', 'сутрин'],
                        ['Noon', 'обяд'],
                        ['Afternoon', 'следобед'],
                        ['Evening', 'вечер'],
                        ['Night', 'нощ'],
                    ]),
                    self::fill('From nine to six', 'Магазинът работи __ девет до шест часа.', ['до', 'на', 'от'], 2,
                        '"Магазинът работи от девет до шест часа." means "The shop is open from nine to six." От ... до ... means "from ... to ...".'),
                ],
            ],
            [
                'name' => 'Seasons and Holidays',
                'description' => 'Seasons, months and the first Bulgarian holidays, with "през" (during) and "без" (without).',
                'exercises' => [
                    self::pairs('Match the seasons', 'Match each English word to its Bulgarian equivalent.', [
                        ['Spring', 'пролет'],
                        ['Summer', 'лято'],
                        ['Autumn', 'есен'],
                        ['Winter', 'зима'],
                        ['December', 'декември'],
                    ]),
                    self::fill('In winter', '__ зимата често вали сняг.', ['Без', 'През', 'За'], 1,
                        '"През зимата често вали сняг." means "In winter it often snows." През means "during".'),
                    self::trueFalse('True or false: Martenitsa', 'On 1 March Bulgarians give each other martenitsi, small red and white decorations.', true,
                        'Correct. The martenitsa (мартеница) is a red and white token of health, given on 1 March for Баба Марта.'),
                    self::fill('Coffee without sugar', 'Пия кафе __ захар.', ['през', 'под', 'без'], 2,
                        '"Пия кафе без захар." means "I drink coffee without sugar."'),
                    self::trueFalse('True or false: Velikden', 'Christmas Eve in Bulgarian is called "Великден".', false,
                        '"Великден" is Easter. Christmas Eve is "Бъдни вечер", and Christmas is "Коледа".'),
                    self::pairs('Match the months', 'Match each month to its Bulgarian name.', [
                        ['January', 'януари'],
                        ['March', 'март'],
                        ['May', 'май'],
                        ['July', 'юли'],
                        ['October', 'октомври'],
                    ]),
                    self::fill('On the fifth of April', 'Рожденият ми ден е __ пети април.', ['в', 'на', 'през'], 1,
                        '"Рожденият ми ден е на пети април." means "My birthday is on the fifth of April." An exact date takes на; a whole month takes през.'),
                ],
            ],
        ];
    }
}
