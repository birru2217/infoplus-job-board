<!-- 🌟 SUBMITTED PAYMENT SLIPS REVIEW 🌟 -->
                    <div class="mt-12 pt-8 border-t border-slate-200 dark:border-slate-700">
                        <h3 class="text-lg font-black text-slate-900 dark:text-white mb-4">Review Submitted Payment Slips</h3>
                        
                        <div class="overflow-x-auto custom-scrollbar">
                            <table class="w-full text-left min-w-[700px]">
                                <thead>
                                    <tr class="text-[10px] sm:text-xs uppercase text-slate-500 bg-slate-50 dark:bg-slate-800/40 border-b">
                                        <th class="py-3 px-4">Employer</th>
                                        <th class="py-3 px-4">Package & Amount</th>
                                        <th class="py-3 px-4">Receipt</th>
                                        <th class="py-3 px-4">Status</th>
                                        <th class="py-3 px-4 text-right">Action</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y text-xs sm:text-sm">
                                    <?php if(empty($employer_slips)): ?>
                                        <tr><td colspan="5" class="py-6 text-center text-slate-400">No payment slips submitted yet.</td></tr>
                                    <?php else: ?>
                                        <?php foreach($employer_slips as $slip): ?>
                                        <tr class="hover:bg-slate-50 dark:hover:bg-slate-800/30">
                                            <td class="py-3 px-4 font-bold">
                                                <?php echo htmlspecialchars($slip['company_name']); ?><br>
                                                <span class="text-[9px] text-slate-400 font-normal"><?php echo htmlspecialchars($slip['email']); ?></span>
                                            </td>
                                            <td class="py-3 px-4 font-bold text-teal-600">
                                                <?php echo htmlspecialchars($slip['package_name']); ?><br>
                                                <span class="text-[10px] text-slate-500">Amount: <?php echo htmlspecialchars($slip['amount']); ?> | Method: <?php echo htmlspecialchars($slip['payment_method']); ?></span>
                                            </td>
                                            <td class="py-3 px-4">
                                                <a href="<?php echo htmlspecialchars($slip['receipt_file']); ?>" target="_blank" class="px-3 py-1 bg-blue-100 text-blue-600 rounded-md font-bold text-[10px]">View Receipt ↗</a>
                                            </td>
                                            <td class="py-3 px-4">
                                                <span class="px-2 py-1 rounded-full text-[9px] font-black uppercase <?php echo $slip['status'] === 'approved' ? 'bg-emerald-100 text-emerald-600' : ($slip['status'] === 'rejected' ? 'bg-rose-100 text-rose-600' : 'bg-amber-100 text-amber-600'); ?>">
                                                    <?php echo $slip['status']; ?>
                                                </span>
                                            </td>
                                            <td class="py-3 px-4 text-right space-x-1">
                                                <?php if($slip['status'] === 'pending'): ?>
                                                <form method="POST" class="inline" onsubmit="return confirm('Approve this payment and grant credits?')">
                                                    <input type="hidden" name="action" value="approve_payment_slip"><input type="hidden" name="active_tab" value="payments">
                                                    <input type="hidden" name="payment_id" value="<?php echo $slip['id']; ?>"><input type="hidden" name="employer_id" value="<?php echo $slip['employer_id']; ?>">
                                                    <input type="hidden" name="package_name" value="<?php echo htmlspecialchars($slip['package_name']); ?>">
                                                    <button type="submit" class="px-3 py-1.5 bg-emerald-500 text-white rounded-md font-bold text-[10px] shadow-md">Approve ✓</button>
                                                </form>
                                                <form method="POST" class="inline" onsubmit="return confirm('Reject this payment?')">
                                                    <input type="hidden" name="action" value="reject_payment_slip"><input type="hidden" name="active_tab" value="payments"><input type="hidden" name="payment_id" value="<?php echo $slip['id']; ?>">
                                                    <button type="submit" class="px-3 py-1.5 bg-rose-500 text-white rounded-md font-bold text-[10px] shadow-md">Reject ✕</button>
                                                </form>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
