<?php

namespace Database\Seeders\Cefr;

use App\Enums\LanguageLevel;

class C2PathSeeder extends CefrPathSeeder
{
    protected function level(): LanguageLevel
    {
        return LanguageLevel::C2;
    }

    protected function pathName(): string
    {
        return 'Mastering Bulgarian';
    }

    protected function lessons(): array
    {
        return [
            [
                'name' => 'The Art of Conversation',
                'description' => 'Paronyms: words that look alike but mean different things.',
                'exercises' => [
                    self::pairs('Match the paronyms', 'Match each English meaning to the right Bulgarian word.', [
                        ['Economic (of the economy)', 'икономически'],
                        ['Economical (thrifty)', 'икономичен'],
                        ['Effective (gets results)', 'ефективен'],
                        ['Striking (makes an impression)', 'ефектен'],
                        ['Practical (useful)', 'практичен'],
                    ]),
                    self::fill('An economical car', 'Новата кола е много __ – харчи само четири литра на сто километра.', ['икономическа', 'икономична', 'икономия'], 1,
                        '"Новата кола е много икономична..." means "The new car is very economical..." Икономически describes the economy itself, as in икономическа криза.'),
                    self::trueFalse('True or false: Efekten', '"Ефектен" and "ефективен" are synonyms.', false,
                        '"Ефектен" means striking or showy, and "ефективен" means effective.'),
                    self::fill('Striking but useless', 'Презентацията беше __ и впечатли всички, но не беше особено полезна.', ['ефективна', 'ефикасна', 'ефектна'], 2,
                        '"Презентацията беше ефектна..." means "The presentation was striking and impressed everyone, but was not very useful." An effective one would have been useful.'),
                    self::trueFalse('True or false: Abonat', '"Абонат" is the subscriber, and "абонамент" is the subscription.', true,
                        'Correct. The two are often mixed up, even by native speakers.'),
                    self::pairs('Match more paronyms', 'Match each English meaning to the right Bulgarian word.', [
                        ['Human', 'човешки'],
                        ['Humane', 'човечен'],
                        ['Sensitive', 'чувствителен'],
                        ['Sensual', 'чувствен'],
                        ['Paternal', 'бащински'],
                    ]),
                    self::fill('A humane attitude', 'Отношението на лекаря към пациентите беше изключително __ и топло.', ['човешко', 'човечно', 'човек'], 1,
                        '"...беше изключително човечно и топло" means "...was exceptionally humane and warm." Човешки means "of humans", човечен means "kind".'),
                ],
            ],
            [
                'name' => 'Voices of Literature',
                'description' => 'Archaic and folk-poetic words, and the rules of the full article and "ѝ".',
                'exercises' => [
                    self::pairs('Match archaic and modern', 'Match each archaic or folk-poetic word with its everyday equivalent.', [
                        ['чедо', 'дете'],
                        ['нозе', 'крака'],
                        ['десница', 'дясна ръка'],
                        ['рече', 'каза'],
                        ['мома', 'девойка'],
                    ]),
                    self::fill('The author is a poet', '__ на книгата е известен поет.', ['Автора', 'Авторът', 'Автор'], 1,
                        '"Авторът на книгата е известен поет." means "The author of the book is a famous poet." The subject takes the full article -ът.'),
                    self::trueFalse('True or false: Full article', 'In standard Bulgarian the full article marks a masculine subject: "Авторът пише", but "Видях автора".', true,
                        'Correct. As a rule of thumb, the full article -ът/-ят goes on the subject and the short -а/-я on everything else.'),
                    self::fill('I gave her a book', 'Подарих __ книга за рождения ден.', ['и', 'й', 'ѝ'], 2,
                        '"Подарих ѝ книга за рождения ден." means "I gave her a book for her birthday." The pronoun "to her" is written ѝ, with a grave accent.'),
                    self::trueFalse('True or false: Writing i', 'The pronoun "ѝ" (to her) may be written as plain "и", because context makes it clear.', false,
                        'The standard requires ѝ, which keeps the pronoun apart from the conjunction и (and).'),
                    self::pairs('Match the literary terms', 'Match each English term to its Bulgarian equivalent.', [
                        ['Metaphor', 'метафора'],
                        ['Epithet', 'епитет'],
                        ['Rhyme', 'рима'],
                        ['Stanza', 'строфа'],
                        ['Narrator', 'разказвач'],
                    ]),
                    self::fill('With his sister', 'Той говори __ сестра си всяка вечер.', ['с', 'със', 'сред'], 1,
                        '"Той говори със сестра си всяка вечер." means "He talks to his sister every evening." Before a word starting with с or з, the preposition с becomes със.'),
                ],
            ],
            [
                'name' => 'Kinship in Detail',
                'description' => 'The full range of in-law terms Bulgarian keeps apart.',
                'exercises' => [
                    self::pairs('Match the in-laws', 'Match each relationship to its Bulgarian term.', [
                        ["Husband's sister", 'зълва'],
                        ["Wife's sister", 'балдъза'],
                        ["Wife's brother", 'шурей'],
                        ["Husband's brother", 'девер'],
                        ["Sister's husband", 'зет'],
                    ]),
                    self::fill("My husband's sister", 'Утре идва на гости моята __ – сестрата на мъжа ми.', ['балдъза', 'зълва', 'етърва'], 1,
                        '"Утре идва на гости моята зълва..." means "My sister-in-law, my husband\'s sister, is visiting tomorrow."'),
                    self::trueFalse('True or false: Etarva', '"Етърва" is what two women married to brothers are to each other.', true,
                        'Correct. The husband\'s brother\'s wife is your етърва.'),
                    self::fill("My sister's husband", 'Мъжът на сестра ми, моят __ Петър, е лекар.', ['девер', 'шурей', 'зет'], 2,
                        '"Мъжът на сестра ми, моят зет Петър, е лекар." means "My sister\'s husband, my brother-in-law Petar, is a doctor." Зет also means son-in-law.'),
                    self::trueFalse('True or false: Shurey', '"Шурей" is the husband\'s brother.', false,
                        'A шурей is the wife\'s brother. The husband\'s brother is a девер.'),
                    self::pairs('Match more in-laws', 'Match each relationship to its Bulgarian term.', [
                        ['Wife\'s father', 'тъст'],
                        ['Husband\'s father', 'свекър'],
                        ['Wife\'s mother', 'тъща'],
                        ['Best man (wedding sponsor)', 'кум'],
                        ['Daughter-in-law', 'снаха'],
                    ]),
                    self::fill('The best man signed', 'На сватбата __ и кумата подписаха като свидетели.', ['кума', 'кумът', 'кум'], 1,
                        '"На сватбата кумът и кумата подписаха като свидетели." means "At the wedding the best man and the matron of honour signed as witnesses." The subject takes the full article.'),
                ],
            ],
            [
                'name' => 'Numbers and Precision',
                'description' => 'Numerals for people, count forms and approximate numbers.',
                'exercises' => [
                    self::pairs('Match the counted phrases', 'Match each English phrase to its Bulgarian equivalent.', [
                        ['Two men', 'двама мъже'],
                        ['Two women', 'две жени'],
                        ['Two chairs', 'два стола'],
                        ['Two children', 'две деца'],
                        ['Five brothers', 'петима братя'],
                    ]),
                    self::fill('Three colleagues', 'На срещата дойдоха __ колеги.', ['три', 'трима', 'третите'], 1,
                        '"На срещата дойдоха трима колеги." means "Three colleagues came to the meeting." Male or mixed groups of people are counted with двама, трима, четирима.'),
                    self::trueFalse('True or false: Count form', 'After a number, masculine nouns for things take the count form: "два стола", not "два столове".', true,
                        'Correct. The count form ends in -а/-я: два стола, три града.'),
                    self::fill('About fifty people', 'Събраха се __ души.', ['петдесети', 'петдесеторка', 'петдесетина'], 2,
                        '"Събраха се петдесетина души." means "About fifty people gathered." The suffix -ина makes a number approximate, so adding "около" would say it twice.'),
                    self::trueFalse('True or false: Dvama zheni', '"Двама жени" is correct when counting women.', false,
                        'Двама is only for men or mixed groups. For women, say "две жени".'),
                    self::pairs('Match the fractions', 'Match each English word to its Bulgarian equivalent.', [
                        ['Half', 'половина'],
                        ['A third', 'една трета'],
                        ['A quarter', 'една четвърт'],
                        ['A pair', 'чифт'],
                        ['A dozen', 'дузина'],
                    ]),
                    self::fill('All three children', 'И __ деца учат в чужбина.', ['тримата', 'три', 'трите'], 2,
                        '"И трите деца учат в чужбина." means "All three children study abroad." Деца is neuter, so it takes трите; тримата is only for men or mixed groups.'),
                ],
            ],
            [
                'name' => 'Property and the Law',
                'description' => 'The notarial and legal vocabulary of owning a home.',
                'exercises' => [
                    self::pairs('Match the property law terms', 'Match each English term to its Bulgarian equivalent.', [
                        ['Title deed', 'нотариален акт'],
                        ['Easement', 'сервитут'],
                        ['Co-ownership', 'съсобственост'],
                        ['Court-ordered seizure', 'възбрана'],
                        ['Cadastral map', 'кадастрална карта'],
                    ]),
                    self::fill('Free of encumbrances', 'Имотът е __ от всякакви тежести.', ['празен', 'свободен', 'отворен'], 1,
                        '"Имотът е свободен от всякакви тежести." means "The property is free of all encumbrances."'),
                    self::trueFalse('True or false: Servitut', 'A "сервитут" is a right to use part of someone else\'s property, for example to cross their land.', true,
                        'Correct. A right of way (право на преминаване) is the most common kind.'),
                    self::fill('The seller undertakes', 'Продавачът се __ да прехвърли собствеността в срок от 30 дни.', ['задължи', 'длъжен', 'задължава'], 2,
                        '"Продавачът се задължава да прехвърли собствеността..." means "The seller undertakes to transfer ownership within 30 days." Contracts use the present imperfective for obligations.'),
                    self::trueFalse('True or false: Sasobstvenost', '"Съсобственост" means that the property belongs to the state.', false,
                        'It means the property is owned jointly by several people.'),
                    self::pairs('Match the property rights', 'Match each English term to its Bulgarian equivalent.', [
                        ['Right of use', 'вещно право на ползване'],
                        ['Building right', 'право на строеж'],
                        ['Notary', 'нотариус'],
                        ['Power of attorney', 'пълномощно'],
                        ['Share of an inheritance', 'наследствен дял'],
                    ]),
                    self::fill('A power of attorney', 'Дадох на брат си нотариално заверено __ да продаде апартамента от мое име.', ['завещание', 'пълномощно', 'удостоверение'], 1,
                        '"...нотариално заверено пълномощно да продаде апартамента от мое име" means "...a notarised power of attorney to sell the flat on my behalf."'),
                ],
            ],
            [
                'name' => 'Toasts and Wishes',
                'description' => 'Wishes for every occasion, with "да", "нека" and "дано".',
                'exercises' => [
                    self::pairs('Match the wishes', 'Match each English meaning to the Bulgarian wish.', [
                        ['Wishing you long life and health', 'Да си жив и здрав'],
                        ['Many happy returns', 'За много години'],
                        ['May it bring you luck', 'Да ти донесе късмет'],
                        ['Congratulations on the baby', 'Честита рожба'],
                        ['May he rest in peace', 'Бог да го прости'],
                    ]),
                    self::fill('Let your dreams come true', 'Вдигам тост и __ се сбъднат всичките ви мечти!', ['ако', 'нека', 'защото'], 1,
                        '"Вдигам тост и нека се сбъднат всичките ви мечти!" means "I raise a toast, and may all your dreams come true!"'),
                    self::trueFalse('True or false: Bog da go prosti', '"Бог да го прости" is said when speaking of someone who has died.', true,
                        'Correct. It means "May God forgive him" and is used much like "may he rest in peace".'),
                    self::fill('Get well soon', 'Дано __ скоро, липсваш ни в офиса!', ['оздравяваш', 'оздравя', 'оздравееш'], 2,
                        '"Дано оздравееш скоро, липсваш ни в офиса!" means "I hope you get well soon, we miss you at the office!" Липсваш shows the wish is for "ти".'),
                    self::trueFalse('True or false: Za mnogo godini', '"За много години" is only said at funerals.', false,
                        'It is said on birthdays and name days, meaning "for many years to come".'),
                    self::pairs('Match the wedding words', 'Match each English word to its Bulgarian equivalent.', [
                        ['Toast', 'наздравица'],
                        ['Newlyweds', 'младоженци'],
                        ['Bride', 'булка'],
                        ['Groom', 'младоженец'],
                        ['Wedding sponsors', 'кумове'],
                    ]),
                    self::fill('Live long and healthy', 'Да сте живи и __ и да се обичате!', ['здрав', 'здраво', 'здрави'], 2,
                        '"Да сте живи и здрави и да се обичате!" means "Wishing you long life and health, and may you love each other!" "Сте" is plural, so the adjective is too.'),
                ],
            ],
            [
                'name' => 'Legal and Administrative Language',
                'description' => 'The fixed phrases of contracts and official documents.',
                'exercises' => [
                    self::pairs('Match the legal phrases', 'Match each English phrase to its Bulgarian equivalent.', [
                        ['Pursuant to', 'съгласно'],
                        ['In view of', 'с оглед на'],
                        ['In the event that', 'в случай че'],
                        ['Hereinafter referred to as', 'наричан по-долу'],
                        ['Null and void', 'нищожен'],
                    ]),
                    self::fill('Pursuant to article 5', '__ чл. 5 от договора наемателят заплаща консумативите.', ['Относно', 'Съгласно', 'Спрямо'], 1,
                        '"Съгласно чл. 5 от договора наемателят заплаща консумативите." means "Pursuant to article 5 of the contract, the tenant pays the utilities."'),
                    self::trueFalse('True or false: V sluchai che', '"В случай че" is written without a comma between "случай" and "че".', true,
                        'Correct. "В случай че" is one conjunction, so no comma goes inside it.'),
                    self::fill('Indefinite term', 'Договорът се сключва за __ срок.', ['безкраен', 'неограничен', 'неопределен'], 2,
                        '"Договорът се сключва за неопределен срок." means "The contract is concluded for an indefinite term."'),
                    self::trueFalse('True or false: Nishtozhen', 'A "нищожен договор" is a contract of very small value.', false,
                        'In law, нищожен means void: the contract has no legal effect.'),
                    self::pairs('Match the court terms', 'Match each English term to its Bulgarian equivalent.', [
                        ['Plaintiff', 'ищец'],
                        ['Defendant', 'ответник'],
                        ['Lawsuit', 'иск'],
                        ['Court ruling', 'съдебно решение'],
                        ['Appeal', 'обжалване'],
                    ]),
                    self::fill('Subject to appeal', 'Решението подлежи на __ в 14-дневен срок.', ['обжалва', 'обжалване', 'обжалван'], 1,
                        '"Решението подлежи на обжалване в 14-дневен срок." means "The ruling may be appealed within 14 days." Подлежи на takes a noun.'),
                ],
            ],
            [
                'name' => 'Philosophy and Ideas',
                'description' => 'Abstract vocabulary, compound words and the prepositions that go with them.',
                'exercises' => [
                    self::pairs('Match the abstract words', 'Match each English term to its Bulgarian equivalent.', [
                        ['Worldview', 'мироглед'],
                        ['Self-awareness', 'самосъзнание'],
                        ['Cause and effect', 'причина и следствие'],
                        ['Theory of knowledge', 'теория на познанието'],
                        ['Universe', 'вселена'],
                    ]),
                    self::fill('Indifferent to opinion', 'Той е безразличен __ чуждото мнение.', ['за', 'към', 'от'], 1,
                        '"Той е безразличен към чуждото мнение." means "He is indifferent to other people\'s opinion." Безразличен takes към.'),
                    self::trueFalse('True or false: Mirogled', '"Мироглед" is built from "мир" (world) and "глед" (view).', true,
                        'Correct. It is a compound, just like the German Weltanschauung.'),
                    self::fill('The theory rests on', 'Теорията почива __ няколко недоказани допускания.', ['от', 'при', 'върху'], 2,
                        '"Теорията почива върху няколко недоказани допускания." means "The theory rests on several unproven assumptions."'),
                    self::trueFalse('True or false: Samosaznanie', '"Самосъзнание" and "съзнание" mean exactly the same thing.', false,
                        '"Съзнание" is consciousness, and "самосъзнание" is awareness of oneself.'),
                    self::pairs('Match the big ideas', 'Match each English word to its Bulgarian equivalent.', [
                        ['Freedom', 'свобода'],
                        ['Responsibility', 'отговорност'],
                        ['Truth', 'истина'],
                        ['Doubt', 'съмнение'],
                        ['Meaning', 'смисъл'],
                    ]),
                    self::fill('Responsible for decisions', 'Човек носи отговорност __ собствените си решения.', ['към', 'от', 'за'], 2,
                        '"Човек носи отговорност за собствените си решения." means "A person is responsible for their own decisions." Отговорност за is for what you answer for; отговорност към is for whom.'),
                ],
            ],
            [
                'name' => 'Time in Language',
                'description' => 'The names of the Bulgarian tenses and the fine points of counterfactuals.',
                'exercises' => [
                    self::pairs('Match the tense names', 'Match each English tense name to its Bulgarian term.', [
                        ['Aorist (past simple)', 'минало свършено време'],
                        ['Imperfect', 'минало несвършено време'],
                        ['Present perfect', 'минало неопределено време'],
                        ['Pluperfect', 'минало предварително време'],
                        ['Future in the past', 'бъдеще време в миналото'],
                    ]),
                    self::fill('If I had known your number', 'Щях да ти се обадя, ако __ номера ти.', ['знам', 'знаех', 'зная'], 1,
                        '"Щях да ти се обадя, ако знаех номера ти." means "I would have called you if I had known your number."'),
                    self::trueFalse('True or false: Da beshe doshal', 'In "Да беше дошъл по-рано!", "да" with a past form expresses regret or an unreal wish.', true,
                        'Correct. It means "If only you had come earlier!"'),
                    self::fill('The film will have started', 'Когато стигнем, филмът вече ще е __ отдавна.', ['започне', 'започваше', 'започнал'], 2,
                        '"Когато стигнем, филмът вече ще е започнал отдавна." means "By the time we arrive, the film will have started long ago." This is the future perfect.'),
                    self::trueFalse('True or false: Future perfect', 'Bulgarian has no future perfect tense.', false,
                        'It does: the бъдеще предварително време, as in "ще съм започнал".'),
                    self::pairs('Match the other tense names', 'Match each English name to its Bulgarian term.', [
                        ['Present tense', 'сегашно време'],
                        ['Future tense', 'бъдеще време'],
                        ['Future perfect', 'бъдеще предварително време'],
                        ['Future perfect in the past', 'бъдеще предварително време в миналото'],
                        ['Renarrative mood', 'преизказно наклонение'],
                    ]),
                    self::fill('I would have seen', 'Ако не бях закъснял, __ да видя началото на филма.', ['ще', 'щях', 'бих'], 1,
                        '"Ако не бях закъснял, щях да видя началото на филма." means "If I had not been late, I would have seen the start of the film."'),
                ],
            ],
            [
                'name' => 'Folklore and National Memory',
                'description' => 'Classic writers, and proverbs every Bulgarian knows.',
                'exercises' => [
                    self::pairs('Match writers and works', 'Match each writer with one of their best-known works.', [
                        ['Ivan Vazov', 'Под игото'],
                        ['Hristo Botev', 'Хаджи Димитър'],
                        ['Aleko Konstantinov', 'Бай Ганьо'],
                        ['Elin Pelin', 'Гераците'],
                        ['Yordan Yovkov', 'Старопланински легенди'],
                    ]),
                    self::fill('Drop by drop', 'Капка по капка __ става.', ['море', 'вир', 'река'], 1,
                        '"Капка по капка вир става." means "Drop by drop a pool forms", so small efforts add up.'),
                    self::trueFalse('True or false: Pod igoto', '"Под игото" is a novel about the April Uprising of 1876.', true,
                        'Correct. Ivan Vazov\'s novel is set in the town of Byala Cherkva before and during the uprising.'),
                    self::fill('Whoever digs a pit', 'Който другиму __ копае, сам пада в нея.', ['гроб', 'път', 'яма'], 2,
                        '"Който другиму яма копае, сам пада в нея." means "Whoever digs a pit for another falls into it himself." The feminine "в нея" points back to яма.'),
                    self::trueFalse('True or false: Barzata rabota', 'The proverb "Бързата работа – срам за майстора" praises doing things quickly.', false,
                        'It warns against haste: rushed work shames the craftsman.'),
                    self::pairs('Match more writers', 'Match each writer with one of their best-known works.', [
                        ['Peyo Yavorov', 'Две хубави очи'],
                        ['Dimcho Debelyanov', 'Да се завърнеш в бащината къща'],
                        ['Pencho Slaveykov', 'Кървава песен'],
                        ['Emilian Stanev', 'Крадецът на праскови'],
                        ['Dimitar Talev', 'Железният светилник'],
                    ]),
                    self::fill('A wolf changes its coat', 'Вълкът козината си мени, __ нрава си – не.', ['и', 'защото', 'но'], 2,
                        '"Вълкът козината си мени, но нрава си – не." means "A wolf changes its coat but not its nature": people do not really change.'),
                ],
            ],
        ];
    }
}
