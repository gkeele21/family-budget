export const learnSteps = [
    {
        id: 'welcome',
        title: 'Ready to Assign',
        message: `This is your <strong>Ready to Assign</strong> balance — it's all the money you have that hasn't been given a job yet. Right now you have <span class="font-mono">$4,200</span> to work with. Your goal? <strong>Give every dollar a job</strong> by assigning it to categories. Let's try it!`,
        target: '[data-tutorial="ready-to-assign"]',
        primaryAction: 'Got It →',
        secondaryAction: 'Skip Tutorial',
        autoAdvance: false,
        actionType: null,
        actionCheck: null,
    },
    {
        id: 'explain_categories',
        title: 'Your Category Envelopes',
        message: `These are your <strong>categories</strong> — think of them as envelopes. Each one holds money for a specific purpose. When you assign money, it goes into an envelope. When you spend, it comes out.`,
        target: '[data-tutorial="category-list"]',
        primaryAction: 'Makes Sense →',
        secondaryAction: null,
        autoAdvance: false,
        actionType: null,
        actionCheck: null,
    },
    {
        id: 'assign_rent',
        title: 'Assign to Rent',
        message: `Let's start with the most important bill. Tap on <strong>Rent</strong> and assign <span class="font-mono">$1,300</span>. Always budget your needs first!`,
        target: '[data-tutorial="category-rent"]',
        primaryAction: 'Tap Rent to continue ☝',
        secondaryAction: null,
        autoAdvance: true,
        actionType: 'assign_category',
        actionCheck: (props) => {
            const groups = props.categoryGroups || [];
            return groups.some((group) =>
                (group.categories || []).some((cat) => {
                    const mb = cat.monthly_budgets?.[0];
                    return mb && parseFloat(mb.budgeted) !== 0;
                }),
            );
        },
    },
    {
        id: 'assign_groceries',
        title: 'Assign to Groceries',
        message: `Nice — you assigned money to Rent! See how <strong>Ready to Assign</strong> went down? Now tap on <strong>Groceries</strong> and assign <span class="font-mono">$500</span>. Think about how much you actually spend each month.`,
        target: '[data-tutorial="category-groceries"]',
        primaryAction: 'Tap Groceries to continue ☝',
        secondaryAction: null,
        autoAdvance: true,
        actionType: 'assign_category',
        actionCheck: (props) => {
            const groups = props.categoryGroups || [];
            let assignedCount = 0;
            groups.forEach((group) => {
                (group.categories || []).forEach((cat) => {
                    const mb = cat.monthly_budgets?.[0];
                    if (mb && parseFloat(mb.budgeted) !== 0) {
                        assignedCount++;
                    }
                });
            });
            return assignedCount >= 2;
        },
    },
    {
        id: 'zero_it_out',
        title: 'Zero It Out',
        message: `Great work! Keep assigning money to categories until <strong>Ready to Assign</strong> reaches <span class="font-mono text-success">$0.00</span>. Remember — $0 doesn't mean you're broke, it means <strong>every dollar has a purpose!</strong>`,
        target: '[data-tutorial="ready-to-assign"]',
        primaryAction: "I'll Assign the Rest →",
        secondaryAction: null,
        autoAdvance: true,
        actionType: 'assign_category',
        actionCheck: (props) => {
            const readyToAssign = parseFloat(props.readyToAssign ?? props.ready_to_assign ?? 0);
            return Math.abs(readyToAssign) < 1;
        },
    },
    {
        id: 'record_transaction',
        title: 'Record a Purchase',
        message: `Now let's spend some money! Tap the <strong>+</strong> button to record a grocery purchase. Try entering <span class="font-mono">$68</span> at the grocery store.`,
        target: '[data-tutorial="fab-button"]',
        primaryAction: 'Tap + to continue ☝',
        secondaryAction: null,
        autoAdvance: true,
        actionType: 'create_transaction',
        actionCheck: (props) => {
            const transactions = props.transactions?.data || props.transactions || [];
            return transactions.length > 0;
        },
    },
    {
        id: 'see_the_effect',
        title: 'See the Effect',
        message: `You just spent <span class="font-mono">$68</span> on groceries. See how Groceries Available went from <span class="font-mono">$500</span> → <span class="font-mono">$432</span>? That's the magic — spending comes out of the <strong>category envelope</strong>, not your total balance. You always know exactly how much you have left.`,
        target: '[data-tutorial="category-groceries"]',
        primaryAction: "That's Cool! →",
        secondaryAction: null,
        autoAdvance: false,
        actionType: null,
        actionCheck: null,
    },
    {
        id: 'complete',
        title: "You've Got It!",
        message: `You've got the basics! You know how to <strong>assign money to envelopes</strong> and how <strong>spending affects them</strong>. Ready to set up your own real budget?`,
        target: null,
        primaryAction: 'Set Up My Budget →',
        secondaryAction: 'Explore on My Own',
        autoAdvance: false,
        actionType: null,
        actionCheck: null,
    },
];

export const setupSteps = [
    {
        id: 'name_budget',
        title: 'Name Your Budget',
        message: `First, let's name your budget and pick your start month. Most people start with the current month.`,
        target: null,
        primaryAction: 'Continue',
        secondaryAction: null,
        autoAdvance: false,
        actionType: null,
        actionCheck: null,
    },
    {
        id: 'add_accounts',
        title: 'Add Your Accounts',
        message: `Now add your bank accounts. Start with your main checking account — use today's balance from your bank app. You can always add more later!`,
        target: null,
        primaryAction: 'Continue',
        secondaryAction: null,
        autoAdvance: false,
        actionType: null,
        actionCheck: null,
    },
    {
        id: 'setup_categories',
        title: 'Set Up Categories',
        message: `Pick a category template to start with, or create your own. You can always customize these later — don't overthink it!`,
        target: null,
        primaryAction: 'Continue',
        secondaryAction: null,
        autoAdvance: false,
        actionType: null,
        actionCheck: null,
    },
    {
        id: 'first_assign',
        title: 'Start Assigning',
        message: `Now the fun part! You have money to assign. Start with your most important bills first — what absolutely needs to be paid this month?`,
        target: '[data-tutorial="ready-to-assign"]',
        primaryAction: "I'll Start Assigning →",
        secondaryAction: null,
        autoAdvance: true,
        actionType: 'assign_category',
        actionCheck: (props) => {
            const readyToAssign = parseFloat(props.readyToAssign ?? props.ready_to_assign ?? 0);
            const groups = props.categoryGroups || [];
            const totalBudgeted = groups.reduce((sum, group) => {
                return sum + (group.categories || []).reduce((catSum, cat) => {
                    const mb = cat.monthly_budgets?.[0];
                    return catSum + (mb ? parseFloat(mb.budgeted) : 0);
                }, 0);
            }, 0);
            return totalBudgeted > 0;
        },
    },
    {
        id: 'add_recurring',
        title: 'Set Up Recurring Bills',
        message: `Set up your recurring bills so Budget Guy can remind you. Think rent, subscriptions, utilities — anything that repeats monthly.`,
        target: null,
        primaryAction: 'Set Up Recurring →',
        secondaryAction: null,
        autoAdvance: false,
        actionType: null,
        actionCheck: null,
    },
    {
        id: 'first_transaction',
        title: 'Record a Transaction',
        message: `Try recording a recent purchase to see it in action! You can use the <strong>+</strong> button or even try the voice feature 🎤`,
        target: '[data-tutorial="fab-button"]',
        primaryAction: 'Tap + to continue ☝',
        secondaryAction: null,
        autoAdvance: true,
        actionType: 'create_transaction',
        actionCheck: (props) => {
            const transactions = props.transactions?.data || props.transactions || [];
            return transactions.length > 0;
        },
    },
    {
        id: 'complete',
        title: 'You\'re All Set!',
        message: `Your budget is ready! The key habit is <strong>recording transactions as they happen</strong>. You've got this!`,
        target: null,
        primaryAction: 'Go to My Budget →',
        secondaryAction: null,
        autoAdvance: false,
        actionType: null,
        actionCheck: null,
    },
];
